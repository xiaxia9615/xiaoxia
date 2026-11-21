import { request } from "./request";
import type { paths } from "./schema";

/** 后端地址 DTO（显式契约声明）。 */
export interface AddressDto {
  address_id?: number;
  id?: number;
  name?: string;
  phone?: string;
  province?: string;
  city?: string;
  district?: string;
  detail?: string;
  is_default?: boolean | number;
  isDefault?: boolean;
  created_at?: string;
}

/** 页面收货地址模型。 */
export interface Address {
  address_id: number;
  name: string;
  phone: string;
  province: string;
  city: string;
  district: string;
  detail: string;
  is_default: boolean;
  created_at?: string;
}

/** DTO -> 收货地址（缺 address_id 的脏数据返回 null）。 */
export function toAddress(raw: AddressDto): Address | null {
  const addressId = raw.address_id ?? raw.id;
  const addressIdNum = Number(addressId);
  if (addressId == null || !Number.isFinite(addressIdNum)) return null;
  return {
    address_id: addressIdNum,
    name: String(raw.name ?? ""),
    phone: String(raw.phone ?? ""),
    province: String(raw.province ?? ""),
    city: String(raw.city ?? ""),
    district: String(raw.district ?? ""),
    detail: String(raw.detail ?? ""),
    is_default: Boolean(raw.is_default ?? raw.isDefault ?? false),
    created_at: raw.created_at ? String(raw.created_at) : undefined,
  };
}

/** Schema 请求体类型（address 端点无 200 响应定义，响应类型暂保留手写，见待汇报清单） */
type AddressSaveReq = paths["/api/v1/address/save"]["post"]["requestBody"]["content"]["application/json"];
type AddressDeleteReq = paths["/api/v1/address/delete"]["post"]["requestBody"]["content"]["application/json"];
type AddressDefaultReq = paths["/api/v1/address/default"]["post"]["requestBody"]["content"]["application/json"];

/** 地址列表（需登录）。错误上抛由 React Query 呈现。 */
export async function fetchAddresses(): Promise<Address[]> {
  const data = await request<{ list?: AddressDto[]; data?: AddressDto[] } | AddressDto[]>(
    "/address/index",
    { auth: true },
  );
  const arr = Array.isArray(data) ? data : (data.data ?? data.list ?? []);
  return arr.map(toAddress).filter((a): a is Address => a !== null);
}

export interface AddressInput {
  name: string;
  phone: string;
  province: string;
  city: string;
  district: string;
  detail: string;
  is_default: boolean;
}

/** 新增地址（需登录）。 */
export async function createAddress(input: AddressInput): Promise<void> {
  const body: AddressSaveReq = { ...input };
  await request<null>("/address/save", {
    method: "POST",
    auth: true,
    body,
  });
}

/** 更新地址（需登录）。 */
export async function updateAddress(
  addressId: number,
  input: AddressInput,
): Promise<void> {
  const body: AddressSaveReq = { id: addressId, ...input };
  await request<null>("/address/save", {
    method: "POST",
    auth: true,
    body,
  });
}

/** 删除地址（需登录）。 */
export async function deleteAddress(addressId: number): Promise<void> {
  const body: AddressDeleteReq = { id: addressId };
  await request<null>("/address/delete", {
    method: "POST",
    auth: true,
    body,
  });
}

/** 设置默认地址（需登录）。 */
export async function setDefaultAddress(addressId: number): Promise<void> {
  const body: AddressDefaultReq = { id: addressId };
  await request<null>("/address/default", {
    method: "POST",
    auth: true,
    body,
  });
}

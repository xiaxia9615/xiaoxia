/**
 * Orval mutator：将 orval 生成的请求适配到项目现有的 request 函数。
 * 确保所有生成的请求自动带上 Authorization: Bearer Token。
 */
import { request } from "./request";

interface OrvalConfig {
  url: string;
  method: string;
  headers?: Record<string, string>;
  data?: unknown;
  params?: Record<string, unknown>;
}

export async function customFetch<T>(config: OrvalConfig): Promise<T> {
  return request<T>(config.url, {
    method: config.method,
    params: config.params,
    body: config.data,
    auth: true,
  });
}

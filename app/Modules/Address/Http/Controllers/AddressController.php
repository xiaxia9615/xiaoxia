<?php

declare(strict_types=1);

namespace App\Modules\Address\Http\Controllers;

use App\Modules\Address\Models\Address;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * @group 收货地址管理
 */
final class AddressController
{
    /**
     * 地址列表（默认地址排最前）。
     */
    public function index(Request $request): JsonResponse
    {
        $list = Address::query()
            ->where('user_id', $request->user()->id)
            ->orderByDesc('is_default')
            ->orderByDesc('id')
            ->get()
            ->map(fn (Address $address): array => $this->toDto($address))
            ->all();

        return response()->json([
            'code' => 0,
            'msg' => 'ok',
            'data' => ['list' => $list, 'total' => count($list)],
        ]);
    }

    /**
     * 新增 / 更新地址（带 id 为更新，不带 id 为新增）。
     */
    public function save(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'id' => ['nullable', 'integer'],
            'name' => ['required', 'string', 'max:50'],
            'phone' => ['required', 'string', 'max:20'],
            'province' => ['nullable', 'string', 'max:50'],
            'city' => ['nullable', 'string', 'max:50'],
            'district' => ['nullable', 'string', 'max:50'],
            'detail' => ['required', 'string', 'max:255'],
            'is_default' => ['nullable', 'boolean'],
        ]);

        $user = $request->user();
        $attributes = [
            'name' => (string) $validated['name'],
            'phone' => (string) $validated['phone'],
            'province' => (string) ($validated['province'] ?? ''),
            'city' => (string) ($validated['city'] ?? ''),
            'district' => (string) ($validated['district'] ?? ''),
            'detail' => (string) $validated['detail'],
        ];

        $address = DB::transaction(function () use ($user, $validated, $attributes): Address {
            if (! empty($validated['id'])) {
                $address = Address::query()
                    ->where('user_id', $user->id)
                    ->where('id', (int) $validated['id'])
                    ->lockForUpdate()
                    ->firstOrFail();
            } else {
                $address = new Address;
                $address->user_id = $user->id;
            }

            $address->fill($attributes);

            $isDefault = (bool) ($validated['is_default'] ?? false);
            if ($isDefault) {
                Address::query()->where('user_id', $user->id)->update(['is_default' => false]);
                $address->is_default = true;
            } elseif ($address->exists) {
                $address->is_default = (bool) $address->is_default;
            }

            $address->save();

            // 首个地址自动设为默认
            if (! $isDefault && $address->is_default === false && Address::query()->where('user_id', $user->id)->count() === 1) {
                $address->is_default = true;
                $address->save();
            }

            return $address;
        });

        return response()->json([
            'code' => 0,
            'msg' => 'ok',
            'data' => $this->toDto($address),
        ]);
    }

    /**
     * 删除地址。
     */
    public function delete(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'id' => ['required', 'integer'],
        ]);

        $deleted = Address::query()
            ->where('user_id', $request->user()->id)
            ->where('id', (int) $validated['id'])
            ->delete();

        if ($deleted === 0) {
            return response()->json(['code' => 1, 'msg' => '地址不存在', 'data' => []], 404);
        }

        return response()->json(['code' => 0, 'msg' => 'ok', 'data' => []]);
    }

    /**
     * 设置默认地址：事务内先清空该用户所有默认标记，再标记目标地址。
     */
    public function setDefault(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'id' => ['required', 'integer'],
        ]);

        $user = $request->user();

        $ok = DB::transaction(function () use ($user, $validated): bool {
            $address = Address::query()
                ->where('user_id', $user->id)
                ->where('id', (int) $validated['id'])
                ->lockForUpdate()
                ->first();

            if ($address === null) {
                return false;
            }

            Address::query()->where('user_id', $user->id)->update(['is_default' => false]);
            $address->is_default = true;
            $address->save();

            return true;
        });

        if (! $ok) {
            return response()->json(['code' => 1, 'msg' => '地址不存在', 'data' => []], 404);
        }

        return response()->json(['code' => 0, 'msg' => 'ok', 'data' => []]);
    }

    /**
     * @return array<string, mixed>
     */
    private function toDto(Address $address): array
    {
        return [
            'address_id' => $address->id,
            'id' => $address->id,
            'name' => $address->name,
            'phone' => $address->phone,
            'province' => $address->province,
            'city' => $address->city,
            'district' => $address->district,
            'detail' => $address->detail,
            'is_default' => $address->is_default,
            'created_at' => optional($address->created_at)->toDateTimeString(),
        ];
    }
}

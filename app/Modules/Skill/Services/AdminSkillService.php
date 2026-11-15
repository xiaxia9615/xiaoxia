<?php

declare(strict_types=1);

namespace App\Modules\Skill\Services;

use App\Models\Product;
use App\Modules\ApiKey\Contracts\ApiCallContract;
use App\Modules\Skill\Enums\SkillStatus;
use App\Modules\Skill\Models\SkillDetail;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class AdminSkillService
{
    public function __construct(
        private readonly ApiCallContract $apiCalls,
    ) {}

    public function paginateSkills(int $perPage = 30)
    {
        $skills = DB::table('skill_details as sd')
            ->leftJoin('products as p', 'p.id', '=', 'sd.product_id')
            ->select(
                'sd.id',
                'sd.product_id',
                'p.name as product_name',
                'sd.endpoint',
                'sd.method',
                'sd.pricing_type',
                'sd.api_key',
                'sd.free_quota',
                'p.status as product_status',
            )
            ->orderByDesc('sd.id')
            ->paginate($perPage);

        // 通过契约批量统计各产品的调用次数（替代原 selectSub 裸查子查询）
        $counts = $this->apiCalls->countsByProduct(
            $skills->getCollection()->pluck('product_id')->filter()->unique()->values()->all()
        );

        $skills->getCollection()->transform(function ($skill) use ($counts) {
            $skill->calls_count = $counts[(int) $skill->product_id] ?? 0;
            $skill->masked_api_key = $this->maskApiKey(
                $skill->api_key ? Crypt::decryptString($skill->api_key) : null
            );
            unset($skill->api_key);

            return $skill;
        });

        return $skills;
    }

    public function paginateCalls(int $perPage = 30, ?string $status = null)
    {
        $statusCode = ($status !== null && $status !== '') ? (int) $status : null;

        return $this->apiCalls->paginateCalls($perPage, $statusCode);
    }

    /**
     * 管理员分页查询待审核的技能列表
     */
    public function paginatePendingSkills(int $perPage = 20): LengthAwarePaginator
    {
        return SkillDetail::query()
            ->with('product')
            ->where('status', SkillStatus::Pending)
            ->orderBy('id')
            ->paginate($perPage);
    }

    public function getSkill(int $id): ?object
    {
        $skill = DB::table('skill_details as sd')
            ->leftJoin('products as p', 'p.id', '=', 'sd.product_id')
            ->select(
                'sd.id',
                'sd.product_id',
                'p.name as product_name',
                'sd.endpoint',
                'sd.method',
                'sd.pricing_type',
                'sd.provider_share',
                'sd.platform_share',
                'sd.influencer_share',
                'sd.donation_share',
                'sd.free_quota',
                'sd.request_template',
                'sd.config',
            )
            ->where('sd.id', $id)
            ->first();

        if (! $skill) {
            return null;
        }

        $skill->request_template = $skill->request_template
            ? json_decode($skill->request_template, true)
            : null;
        $skill->config = $skill->config
            ? json_decode($skill->config, true)
            : null;

        $encryptedKey = DB::table('skill_details')->where('id', $id)->value('api_key');
        $skill->masked_api_key = $this->maskApiKey(
            $encryptedKey ? Crypt::decryptString($encryptedKey) : null
        );

        return $skill;
    }

    public function stats(): array
    {
        $callStats = $this->apiCalls->callStats();

        return [
            'skills' => DB::table('skill_details')->count(),
            'calls_total' => $callStats['total'],
            'calls_today' => $callStats['today'],
            'calls_success' => $callStats['success'],
        ];
    }

    /**
     * 创建技能：同时创建 Product（type=1）和 SkillDetail，事务包裹
     *
     * @param  array  $data  已校验数据，price 为元
     * @return array{0:Product,1:SkillDetail}
     */
    public function create(array $data): array
    {
        return DB::transaction(function () use ($data) {
            $product = Product::create([
                'user_id' => auth()->id(),
                'type' => 1,
                'name' => $data['name'],
                'slug' => Str::slug($data['name']).'-'.uniqid(),
                'description' => $data['description'] ?? null,
                'price' => (int) round((float) $data['price'] * 1000),
                'status' => (int) ($data['status'] ?? 1),
            ]);

            $skillDetail = SkillDetail::create([
                'product_id' => $product->id,
                'provider_id' => $data['provider_id'] ?? null,
                'status' => SkillStatus::Published,
                'endpoint' => $data['endpoint'] ?? '',
                'method' => $data['method'] ?? 'POST',
                'pricing_type' => (int) ($data['pricing_type'] ?? 1),
                'system_prompt' => $data['system_prompt'] ?? null,
                'free_quota' => (int) ($data['free_quota'] ?? 0),
            ]);

            return [$product, $skillDetail];
        });
    }

    /**
     * 查找技能用于编辑（含 Product 关联）
     */
    public function findForEdit(int $id): SkillDetail
    {
        return SkillDetail::with('product')->findOrFail($id);
    }

    /**
     * 更新技能：同时更新 Product 和 SkillDetail，事务包裹
     *
     * @param  array  $data  已校验数据，price 为元
     * @return array{0:Product,1:SkillDetail}
     */
    public function update(int $id, array $data): array
    {
        return DB::transaction(function () use ($id, $data) {
            $skillDetail = SkillDetail::findOrFail($id);
            $product = Product::findOrFail($skillDetail->product_id);

            $product->update([
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'price' => (int) round((float) $data['price'] * 1000),
            ]);

            $skillDetail->update([
                'provider_id' => $data['provider_id'] ?? null,
                'endpoint' => $data['endpoint'] ?? $skillDetail->endpoint,
                'method' => $data['method'] ?? $skillDetail->method,
                'pricing_type' => (int) ($data['pricing_type'] ?? $skillDetail->pricing_type),
                'system_prompt' => $data['system_prompt'] ?? null,
                'free_quota' => (int) ($data['free_quota'] ?? $skillDetail->free_quota),
            ]);

            return [$product, $skillDetail];
        });
    }

    /**
     * 切换技能上下架状态：仅操作 Product 的 status 字段
     */
    public function toggleStatus(int $id): Product
    {
        return DB::transaction(function () use ($id) {
            $skillDetail = SkillDetail::findOrFail($id);
            $product = Product::findOrFail($skillDetail->product_id);
            $product->update(['status' => $product->status === 1 ? 0 : 1]);

            return $product;
        });
    }

    private function maskApiKey(?string $key): string
    {
        if ($key === null || $key === '') {
            return '(未配置)';
        }
        if (strlen($key) <= 8) {
            return '****';
        }

        return substr($key, 0, 3).'****'.substr($key, -4);
    }
}

<?php

declare(strict_types=1);

namespace App\Modules\Skill\Services;

use App\Modules\ApiKey\Contracts\ApiCallContract;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

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

        if (!$skill) {
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
            'skills'        => DB::table('skill_details')->count(),
            'calls_total'   => $callStats['total'],
            'calls_today'   => $callStats['today'],
            'calls_success' => $callStats['success'],
        ];
    }

    private function maskApiKey(?string $key): string
    {
        if ($key === null || $key === '') {
            return '(未配置)';
        }
        if (strlen($key) <= 8) {
            return '****';
        }
        return substr($key, 0, 3) . '****' . substr($key, -4);
    }
}

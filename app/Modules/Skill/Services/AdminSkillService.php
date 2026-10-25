<?php

declare(strict_types=1);

namespace App\Modules\Skill\Services;

use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

final class AdminSkillService
{
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
            ->selectSub(
                DB::table('api_calls')
                    ->selectRaw('COUNT(*)')
                    ->whereColumn('product_id', 'sd.product_id'),
                'calls_count'
            )
            ->orderByDesc('sd.id')
            ->paginate($perPage);

        $skills->getCollection()->transform(function ($skill) {
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
        $query = DB::table('api_calls')
            ->select('id', 'request_id', 'user_id', 'product_id', 'status_code', 'duration_ms', 'cost', 'created_at')
            ->orderByDesc('id');

        if ($status !== null && $status !== '') {
            $query->where('status_code', (int) $status);
        }

        return $query->paginate($perPage);
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
        return [
            'skills'        => DB::table('skill_details')->count(),
            'calls_total'   => DB::table('api_calls')->count(),
            'calls_today'   => DB::table('api_calls')->whereDate('created_at', today())->count(),
            'calls_success' => DB::table('api_calls')->where('status_code', 200)->count(),
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

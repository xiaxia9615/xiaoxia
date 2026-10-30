<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Modules\AiProvider\Models\AiModel;
use App\Modules\AiProvider\Models\AiProvider;
use Illuminate\Database\Seeder;

/**
 * 主流 AI 供应商模型数据字典
 *
 * 价格说明：ai_models 表价格为整型字段，为保证精度，
 * 统一按「美分 / 百万 token」（即 USD/1M × 100）换算存储。
 * 供应商通过 name 查询真实 ID，不硬编码 provider_id；
 * 全部使用 updateOrCreate / firstOrCreate，可重复执行。
 */
class AiModelsSeeder extends Seeder
{
    public function run(): void
    {
        // 1. 确保海外供应商存在（deepseek / qwen-dashscope 已由 DomesticAiProvidersSeeder 创建）
        $providers = [
            [
                'name' => 'openai',
                'display_name' => 'OpenAI',
                'base_url' => 'https://api.openai.com/v1',
                'driver' => 'openai_compatible',
                'model_name' => 'gpt-4o',
            ],
            [
                'name' => 'anthropic',
                'display_name' => 'Anthropic',
                'base_url' => 'https://api.anthropic.com',
                'driver' => 'openai_compatible',
                'model_name' => 'claude-3-5-sonnet-20241022',
            ],
            [
                'name' => 'google-gemini',
                'display_name' => 'Google Gemini',
                'base_url' => 'https://generativelanguage.googleapis.com/v1beta',
                'driver' => 'openai_compatible',
                'model_name' => 'gemini-1.5-pro',
            ],
        ];

        foreach ($providers as $provider) {
            AiProvider::firstOrCreate(
                ['name' => $provider['name']],
                array_merge($provider, [
                    'api_key' => null,
                    'enabled' => true,
                ])
            );
        }

        // 2. 模型数据（价格单位：美分 / 百万 token）
        $models = [
            // OpenAI
            ['provider' => 'openai', 'name' => 'gpt-4o', 'display_name' => 'GPT-4o', 'input' => 500, 'output' => 1500],
            ['provider' => 'openai', 'name' => 'gpt-4o-mini', 'display_name' => 'GPT-4o mini', 'input' => 15, 'output' => 60],
            ['provider' => 'openai', 'name' => 'gpt-3.5-turbo', 'display_name' => 'GPT-3.5 Turbo', 'input' => 50, 'output' => 150],
            // Anthropic
            ['provider' => 'anthropic', 'name' => 'claude-3-5-sonnet-20241022', 'display_name' => 'Claude 3.5 Sonnet', 'input' => 300, 'output' => 1500],
            ['provider' => 'anthropic', 'name' => 'claude-3-haiku-20240307', 'display_name' => 'Claude 3 Haiku', 'input' => 25, 'output' => 125],
            // Google
            ['provider' => 'google-gemini', 'name' => 'gemini-1.5-pro', 'display_name' => 'Gemini 1.5 Pro', 'input' => 350, 'output' => 1050],
            ['provider' => 'google-gemini', 'name' => 'gemini-1.5-flash', 'display_name' => 'Gemini 1.5 Flash', 'input' => 35, 'output' => 105],
            // DeepSeek
            ['provider' => 'deepseek', 'name' => 'deepseek-chat', 'display_name' => 'DeepSeek Chat (V3)', 'input' => 14, 'output' => 28],
            ['provider' => 'deepseek', 'name' => 'deepseek-reasoner', 'display_name' => 'DeepSeek Reasoner (R1)', 'input' => 55, 'output' => 219],
            // Alibaba
            ['provider' => 'qwen-dashscope', 'name' => 'qwen-max', 'display_name' => 'Qwen Max', 'input' => 240, 'output' => 960],
            ['provider' => 'qwen-dashscope', 'name' => 'qwen-turbo', 'display_name' => 'Qwen Turbo', 'input' => 30, 'output' => 60],
        ];

        foreach ($models as $model) {
            $provider = AiProvider::where('name', $model['provider'])->first();

            if (! $provider instanceof AiProvider) {
                continue;
            }

            AiModel::updateOrCreate(
                ['provider_id' => $provider->id, 'name' => $model['name']],
                [
                    'display_name' => $model['display_name'],
                    'input_price_per_1k' => $model['input'],
                    'output_price_per_1k' => $model['output'],
                    'enabled' => true,
                ]
            );
        }
    }
}

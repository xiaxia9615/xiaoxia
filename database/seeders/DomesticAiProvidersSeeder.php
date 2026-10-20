<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Modules\AiProvider\Models\AiProvider;
use Illuminate\Database\Seeder;

/**
 * 国内 14 家大模型供应商入库
 *
 * driver 为空 => GlmClient（智谱专用）
 * driver = openai_compatible => OpenAiCompatibleClient（通用 OpenAI 兼容协议）
 * api_key 统一留空，接入时通过后台/配置逐个填充（模型层自动加密存储）
 */
class DomesticAiProvidersSeeder extends Seeder
{
    public function run(): void
    {
        $providers = [
            [
                'name' => 'zhipu-glm',
                'display_name' => '智谱 GLM（bigmodel）',
                'base_url' => 'https://open.bigmodel.cn/api/paas/v4',
                'driver' => null,
                'model_name' => 'glm-4',
                'cost_per_1k_input' => '0.050000',
                'cost_per_1k_output' => '0.050000',
            ],
            [
                'name' => 'deepseek',
                'display_name' => '深度求索 DeepSeek',
                'base_url' => 'https://api.deepseek.com',
                'driver' => 'openai_compatible',
                'model_name' => 'deepseek-chat',
                'cost_per_1k_input' => '0.001000',
                'cost_per_1k_output' => '0.002000',
            ],
            [
                'name' => 'qwen-dashscope',
                'display_name' => '阿里云通义千问（DashScope）',
                'base_url' => 'https://dashscope.aliyuncs.com/compatible-mode/v1',
                'driver' => 'openai_compatible',
                'model_name' => 'qwen-plus',
                'cost_per_1k_input' => '0.000800',
                'cost_per_1k_output' => '0.002000',
            ],
            [
                'name' => 'moonshot-kimi',
                'display_name' => '月之暗面 Kimi（Moonshot）',
                'base_url' => 'https://api.moonshot.cn/v1',
                'driver' => 'openai_compatible',
                'model_name' => 'moonshot-v1-8k',
                'cost_per_1k_input' => '0.012000',
                'cost_per_1k_output' => '0.012000',
            ],
            [
                'name' => 'doubao-ark',
                'display_name' => '字节跳动豆包（火山方舟）',
                'base_url' => 'https://ark.cn-beijing.volces.com/api/v3',
                'driver' => 'openai_compatible',
                'model_name' => 'doubao-pro-32k',
                'cost_per_1k_input' => '0.000800',
                'cost_per_1k_output' => '0.002000',
            ],
            [
                'name' => 'baidu-ernie',
                'display_name' => '百度文心一言（千帆）',
                'base_url' => 'https://qianfan.baidubce.com/v2',
                'driver' => 'openai_compatible',
                'model_name' => 'ernie-4.0-8k',
                'cost_per_1k_input' => '0.040000',
                'cost_per_1k_output' => '0.120000',
            ],
            [
                'name' => 'tencent-hunyuan',
                'display_name' => '腾讯混元',
                'base_url' => 'https://api.hunyuan.cloud.tencent.com/v1',
                'driver' => 'openai_compatible',
                'model_name' => 'hunyuan-turbo',
                'cost_per_1k_input' => '0.015000',
                'cost_per_1k_output' => '0.060000',
            ],
            [
                'name' => 'iflytek-spark',
                'display_name' => '科大讯飞星火',
                'base_url' => 'https://spark-api-open.xf-yun.com/v1',
                'driver' => 'openai_compatible',
                'model_name' => 'generalv3.5',
                'cost_per_1k_input' => '0.018000',
                'cost_per_1k_output' => '0.018000',
            ],
            [
                'name' => 'minimax',
                'display_name' => 'MiniMax',
                'base_url' => 'https://api.minimax.chat/v1',
                'driver' => 'openai_compatible',
                'model_name' => 'abab6.5s-chat',
                'cost_per_1k_input' => '0.003000',
                'cost_per_1k_output' => '0.003000',
            ],
            [
                'name' => 'baichuan',
                'display_name' => '百川智能',
                'base_url' => 'https://api.baichuan-ai.com/v1',
                'driver' => 'openai_compatible',
                'model_name' => 'Baichuan4',
                'cost_per_1k_input' => '0.100000',
                'cost_per_1k_output' => '0.100000',
            ],
            [
                'name' => 'lingyi-wanwu',
                'display_name' => '零一万物',
                'base_url' => 'https://api.lingyiwanwu.com/v1',
                'driver' => 'openai_compatible',
                'model_name' => 'yi-large',
                'cost_per_1k_input' => '0.020000',
                'cost_per_1k_output' => '0.020000',
            ],
            [
                'name' => 'stepfun',
                'display_name' => '阶跃星辰',
                'base_url' => 'https://api.stepfun.com/v1',
                'driver' => 'openai_compatible',
                'model_name' => 'step-1-8k',
                'cost_per_1k_input' => '0.005000',
                'cost_per_1k_output' => '0.014000',
            ],
            [
                'name' => 'sensetime-sensechat',
                'display_name' => '商汤日日新',
                'base_url' => 'https://api.sensenova.cn/compatible-mode/v1',
                'driver' => 'openai_compatible',
                'model_name' => 'SenseChat-5',
                'cost_per_1k_input' => '0.015000',
                'cost_per_1k_output' => '0.015000',
            ],
            [
                'name' => 'siliconflow',
                'display_name' => '硅基流动 SiliconFlow',
                'base_url' => 'https://api.siliconflow.cn/v1',
                'driver' => 'openai_compatible',
                'model_name' => 'Qwen/Qwen2.5-7B-Instruct',
                'cost_per_1k_input' => '0.000350',
                'cost_per_1k_output' => '0.000350',
            ],
        ];

        foreach ($providers as $provider) {
            AiProvider::updateOrCreate(
                ['name' => $provider['name']],
                array_merge($provider, [
                    'api_key' => null,
                    'enabled' => true,
                ])
            );
        }
    }
}

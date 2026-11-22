<?php

declare(strict_types=1);

namespace App\Modules\User\Plugins;

use Closure;
use Yansongda\Artful\Contract\PluginInterface;
use Yansongda\Artful\Logger;
use Yansongda\Artful\Rocket;

/**
 * 通用支付宝 API 方法插件。
 *
 * 从 params[_method] 读取接口方法名，其余参数合并到 payload。
 * 默认参数放在 biz_content 中（标准 API），
 * 设置 params[_top_level] = true 时参数放在顶层（OAuth 类 API）。
 */
final class AlipayMethodPlugin implements PluginInterface
{
    public function assembly(Rocket $rocket, Closure $next): Rocket
    {
        Logger::debug('[Alipay][MethodPlugin] 插件开始装载', ['rocket' => $rocket]);

        $params = $rocket->getParams();
        $method = $params['_method'] ?? '';
        $topLevel = $params['_top_level'] ?? false;
        unset($params['_method'], $params['_top_level']);

        if ($topLevel) {
            // OAuth 类 API：参数放在顶层
            $rocket->mergePayload(array_merge(
                ['method' => $method],
                $params,
            ));
        } else {
            // 标准 API：参数放在 biz_content
            $rocket->mergePayload([
                'method' => $method,
                'biz_content' => $params,
            ]);
        }

        Logger::info('[Alipay][MethodPlugin] 插件装载完毕', ['rocket' => $rocket]);

        return $next($rocket);
    }
}

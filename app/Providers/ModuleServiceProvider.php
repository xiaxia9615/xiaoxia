<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use RuntimeException;

final class ModuleServiceProvider extends ServiceProvider
{
    /** @var string[] */
    private array $modules = [];

    public function register(): void
    {
        $list = config('modules.list', []);
        $pattern = config('modules.provider_pattern');

        if (! is_array($list) || empty($list)) {
            return;
        }

        foreach ($list as $module) {
            $providerClass = sprintf($pattern, $module, $module);

            if (! class_exists($providerClass)) {
                throw new RuntimeException(
                    "模块 [{$module}] 的 ServiceProvider 不存在：{$providerClass}"
                );
            }

            $this->app->register($providerClass);
            $this->modules[] = $module;
        }
    }

    /** @return string[] */
    public function loadedModules(): array
    {
        return $this->modules;
    }
}

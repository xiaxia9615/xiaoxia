<?php

declare(strict_types=1);

namespace App\Modules\Payment\Providers;

use App\Modules\Payment\Contracts\PaymentGatewayInterface;
use App\Modules\Payment\Services\MockPaymentGateway;
use Illuminate\Support\ServiceProvider;

final class PaymentServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(
            PaymentGatewayInterface::class,
            MockPaymentGateway::class,
        );
    }

    public function boot(): void
    {
        // Payment 模块当前无独立迁移与路由，仅提供网关抽象与绑定
    }
}

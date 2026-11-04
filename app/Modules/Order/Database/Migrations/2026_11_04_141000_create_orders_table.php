<?php

declare(strict_types=1);

namespace App\Modules\Order\Database\Migrations;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_no', 32)->unique()->comment('订单号：YYYYMMDD+8位随机序列');
            $table->unsignedBigInteger('user_id')->index()->comment('买家ID');
            $table->unsignedBigInteger('product_id')->index()->comment('商品ID');
            $table->unsignedBigInteger('amount')->default(0)->comment('价格快照（毫），下单时刻商品价格');
            $table->string('product_name', 100)->comment('商品名称快照');
            $table->json('product_snapshot')->nullable()->comment('商品信息完整快照');
            $table->string('status', 16)->default('pending')->comment('pending|paid|cancelled|completed|refunded');
            $table->timestamp('paid_at')->nullable()->comment('支付时间');
            $table->timestamps();
            $table->index('status');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};

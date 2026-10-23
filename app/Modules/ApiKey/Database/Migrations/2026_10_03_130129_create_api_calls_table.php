<?php

declare(strict_types=1);

namespace App\Modules\ApiKey\Database\Migrations;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('api_calls', function (Blueprint $table) {
            $table->id();
            $table->string('request_id', 36)->unique()->comment('请求ID');
            $table->foreignId('user_id')->constrained()->cascadeOnDelete()->comment('调用者');
            $table->foreignId('api_key_id')->constrained()->cascadeOnDelete()->comment('使用的key');
            $table->foreignId('product_id')->constrained()->cascadeOnDelete()->comment('调用的技能/商品');
            $table->unsignedBigInteger('provider_id')->comment('提供者ID，冗余');
            $table->json('input')->nullable()->comment('请求参数');
            $table->json('output')->nullable()->comment('返回结果');
            $table->unsignedSmallInteger('status_code')->default(200)->comment('HTTP状态码');
            $table->unsignedInteger('duration_ms')->default(0)->comment('耗时毫秒');
            $table->unsignedBigInteger('cost')->default(0)->comment('总费用，分');
            $table->unsignedBigInteger('provider_income')->default(0)->comment('提供者收入');
            $table->unsignedBigInteger('platform_income')->default(0)->comment('平台收入');
            $table->unsignedBigInteger('influencer_income')->default(0)->comment('达人收入');
            $table->unsignedBigInteger('donation_amount')->default(0)->comment('公益金额');
            $table->timestamps();

            $table->index('user_id');
            $table->index('product_id');
            $table->index('provider_id');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('api_calls');
    }
};

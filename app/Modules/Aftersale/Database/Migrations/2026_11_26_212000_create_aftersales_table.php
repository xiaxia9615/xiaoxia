<?php

declare(strict_types=1);

namespace App\Modules\Aftersale\Database\Migrations;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('aftersales', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('order_id')->index();
            $table->unsignedBigInteger('user_id')->index();
            $table->string('order_no', 32)->index();
            $table->tinyInteger('type')->default(1)->comment('1退款 2退货退款 3换货');
            $table->string('status', 16)->default('pending')->comment('pending|approved|rejected|completed|cancelled');
            $table->string('reason', 500)->comment('申请原因');
            $table->text('description')->nullable()->comment('问题描述');
            $table->unsignedBigInteger('refund_amount')->default(0)->comment('退款金额（毫）');
            $table->string('admin_note', 255)->nullable()->comment('管理员备注');
            $table->timestamp('processed_at')->nullable()->comment('处理时间');
            $table->timestamps();
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('aftersales');
    }
};

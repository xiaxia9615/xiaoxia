<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('withdrawals', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id')->index();
            $table->string('amount', 32)->comment('提现金额（毫）');
            $table->string('status', 16)->default('pending')->index()->comment('pending | approved | rejected');
            $table->string('remark')->nullable()->comment('审核备注');
            $table->timestamp('audited_at')->nullable()->comment('审核时间');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('withdrawals');
    }
};

<?php

declare(strict_types=1);

namespace App\Modules\Ledger\Database\Migrations;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_usage_deductions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->index();
            $table->unsignedBigInteger('provider_id')->index();
            $table->decimal('cost', 12, 6)->comment('扣费金额（元）');
            $table->unsignedInteger('input_tokens');
            $table->unsignedInteger('output_tokens');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_usage_deductions');
    }
};

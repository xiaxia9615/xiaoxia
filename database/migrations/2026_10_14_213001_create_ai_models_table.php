<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_models', function (Blueprint $table) {
            $table->id();
            $table->foreignId('provider_id')->constrained('ai_providers')->cascadeOnDelete();
            $table->string('name', 100)->comment('模型标识名');
            $table->string('display_name', 200)->comment('模型显示名称');
            $table->unsignedInteger('input_price_per_1k')->default(0)->comment('每千 input token 价格（分）');
            $table->unsignedInteger('output_price_per_1k')->default(0)->comment('每千 output token 价格（分）');
            $table->boolean('enabled')->default(true)->comment('是否启用');
            $table->timestamps();

            $table->unique(['provider_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_models');
    }
};

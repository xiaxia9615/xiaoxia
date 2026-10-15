<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_providers', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->unique()->comment('供应商标识名');
            $table->string('display_name', 200)->comment('供应商显示名称');
            $table->string('base_url', 500)->comment('API 基础地址');
            $table->text('api_key')->nullable()->comment('API 密钥（加密存储）');
            $table->boolean('enabled')->default(true)->comment('是否启用');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_providers');
    }
};

<?php

declare(strict_types=1);

namespace App\Modules\AiProvider\Database\Migrations;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ai_providers', function (Blueprint $table) {
            // 客户端驱动类型：openai_compatible 走通用 OpenAI 兼容客户端
            $table->string('driver', 50)->nullable()->after('base_url')->comment('客户端驱动类型');
            // 默认调用的模型标识（OpenAI 兼容协议的 model 参数）
            $table->string('model_name', 200)->nullable()->after('driver')->comment('默认模型标识');
        });
    }

    public function down(): void
    {
        Schema::table('ai_providers', function (Blueprint $table) {
            $table->dropColumn(['driver', 'model_name']);
        });
    }
};

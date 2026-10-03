<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('skill_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('endpoint', 255)->comment('调用地址');
            $table->string('method', 10)->default('POST')->comment('GET/POST');
            $table->tinyInteger('pricing_type')->default(1)->comment('1按次 2按月 3按量');
            $table->unsignedTinyInteger('provider_share')->default(80)->comment('提供者分成%');
            $table->unsignedTinyInteger('platform_share')->default(10)->comment('平台分成%');
            $table->unsignedTinyInteger('influencer_share')->default(5)->comment('达人分成%');
            $table->unsignedTinyInteger('donation_share')->default(5)->comment('公益分成%');
            $table->unsignedInteger('free_quota')->default(0)->comment('免费额度次数');
            $table->json('config')->nullable()->comment('技能配置');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('skill_details');
    }
};

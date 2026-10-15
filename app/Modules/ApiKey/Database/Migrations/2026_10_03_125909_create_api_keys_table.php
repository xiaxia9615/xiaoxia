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
        Schema::create('api_keys', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name', 100)->comment('key名称');
            $table->string('key_hash', 64)->unique()->comment('SHA256哈希');
            $table->string('key_prefix', 12)->comment('前8位用于识别');
            $table->tinyInteger('status')->default(1)->comment('1启用 0禁用');
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->index('user_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('api_keys');
    }
};

<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('messages', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id')->index()->comment('用户 ID');
            $table->string('title')->comment('消息标题');
            $table->text('content')->comment('消息内容');
            $table->boolean('is_read')->default(false)->index()->comment('是否已读');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};

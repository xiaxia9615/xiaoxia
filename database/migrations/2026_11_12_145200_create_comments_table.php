<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('comments', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id')->index()->comment('用户 ID');
            $table->unsignedBigInteger('product_id')->index()->comment('商品 ID');
            $table->text('content')->comment('评论内容');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comments');
    }
};

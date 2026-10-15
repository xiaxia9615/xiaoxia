<?php

declare(strict_types=1);

namespace App\Modules\Product\Database\Migrations;


use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete()->comment('提供者');
            $table->tinyInteger('type')->default(1)->comment('1技能 2商品');
            $table->string('name', 100);
            $table->string('slug', 100)->unique();
            $table->string('description', 500)->nullable();
            $table->string('cover', 255)->nullable()->comment('封面图URL');
            $table->unsignedBigInteger('price')->default(0)->comment('单价，单位分');
            $table->tinyInteger('status')->default(0)->comment('0待审 1上架 2下架 3拒绝');
            $table->string('audit_note', 255)->nullable()->comment('审核备注');
            $table->unsignedInteger('sales_count')->default(0)->comment('销量');
            $table->unsignedInteger('view_count')->default(0)->comment('浏览量');
            $table->timestamps();
            $table->softDeletes();

            $table->index('user_id');
            $table->index('type');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};

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
        Schema::table('products', function (Blueprint $table) {
            $table->string('nature', 16)->default('physical')->comment('商品性质：physical 实物 / virtual 虚拟')->after('type');
            $table->integer('quota')->default(0)->comment('虚拟商品赠送额度（分）')->after('nature');
            $table->unsignedBigInteger('skill_id')->nullable()->comment('虚拟商品绑定的技能ID')->after('quota');
            $table->index('nature');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['nature', 'quota', 'skill_id']);
        });
    }
};

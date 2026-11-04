<?php

declare(strict_types=1);

namespace App\Modules\Distributor\Database\Migrations;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('distributor_relations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->comment('下级用户ID');
            $table->unsignedBigInteger('distributor_id')->comment('上级达人用户ID');
            $table->timestamps();

            // 一个下级用户只能绑定一个上级达人
            $table->unique('user_id', 'uniq_user');
            $table->index('distributor_id', 'idx_distributor');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('distributor_relations');
    }
};

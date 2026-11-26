<?php

declare(strict_types=1);

namespace App\Modules\Favor\Database\Migrations;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('favorites', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id')->index();
            $table->unsignedBigInteger('product_id')->index();
            $table->timestamps();
            $table->unique(['user_id', 'product_id'], 'uniq_user_product');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('favorites');
    }
};

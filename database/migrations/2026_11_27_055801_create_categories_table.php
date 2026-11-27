<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 50);
            $table->string('slug', 50)->unique();
            $table->unsignedBigInteger('parent_id')->default(0)->index();
            $table->integer('sort')->default(0);
            $table->tinyInteger('status')->default(1);
            $table->timestamps();
        });

        Schema::table('products', function (Blueprint $table): void {
            $table->unsignedBigInteger('category_id')->default(0)->index()->after('type');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            $table->dropColumn('category_id');
        });
        Schema::dropIfExists('categories');
    }
};

<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('skill_details', function (Blueprint $table): void {
            $table->unsignedBigInteger('provider_id')->nullable()->after('product_id');
            $table->text('system_prompt')->nullable()->after('config');
        });
    }

    public function down(): void
    {
        Schema::table('skill_details', function (Blueprint $table): void {
            $table->dropColumn(['provider_id', 'system_prompt']);
        });
    }
};

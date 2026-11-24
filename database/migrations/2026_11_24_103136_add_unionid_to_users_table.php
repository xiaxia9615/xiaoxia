<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'unionid')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->string('unionid', 100)->nullable()->unique()->after('provider_id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('users', 'unionid')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->dropColumn('unionid');
            });
        }
    }
};

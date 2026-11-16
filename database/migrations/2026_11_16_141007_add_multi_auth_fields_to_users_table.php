<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            if (! Schema::hasColumn('users', 'provider')) {
                $table->string('provider', 20)->nullable()->after('phone');
            }
            if (! Schema::hasColumn('users', 'provider_id')) {
                $table->string('provider_id', 100)->nullable()->after('provider');
            }

            $table->index(['provider', 'provider_id']);

            $table->string('email')->nullable()->change();
            $table->string('password')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropIndex(['provider', 'provider_id']);

            $table->string('email')->nullable(false)->change();
            $table->string('password')->nullable(false)->change();

            if (Schema::hasColumn('users', 'provider')) {
                $table->dropColumn('provider');
            }
            if (Schema::hasColumn('users', 'provider_id')) {
                $table->dropColumn('provider_id');
            }
        });
    }
};

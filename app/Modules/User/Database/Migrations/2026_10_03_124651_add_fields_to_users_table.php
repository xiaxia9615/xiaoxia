<?php

declare(strict_types=1);

namespace App\Modules\User\Database\Migrations;


use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('phone', 20)->nullable()->after('email');
            $table->string('avatar', 255)->nullable()->after('phone');
            $table->tinyInteger('status')->default(1)->after('avatar')->comment('1正常 0禁用');
            $table->unsignedBigInteger('referrer_id')->nullable()->after('status')->comment('推荐人ID');
            $table->index('referrer_id');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['referrer_id']);
            $table->dropColumn(['phone', 'avatar', 'status', 'referrer_id']);
        });
    }
};

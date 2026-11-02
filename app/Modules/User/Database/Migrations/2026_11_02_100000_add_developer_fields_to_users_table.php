<?php

declare(strict_types=1);

namespace App\Modules\User\Database\Migrations;

use App\Modules\User\Enums\DeveloperStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->boolean('is_developer')->default(false)->after('is_admin')->comment('是否开发者');
            $table->string('developer_status', 20)->default(DeveloperStatus::None->value)->after('is_developer')->comment('开发者状态 none/pending/approved/rejected');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn(['is_developer', 'developer_status']);
        });
    }
};

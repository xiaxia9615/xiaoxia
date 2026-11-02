<?php

declare(strict_types=1);

namespace App\Modules\Skill\Database\Migrations;

use App\Modules\Skill\Enums\SkillStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('skill_details', function (Blueprint $table): void {
            // 历史数据无状态记录，默认 published，避免破坏现有已上架技能
            $table->string('status', 20)->default(SkillStatus::Published->value)->after('product_id')->comment('技能状态 draft/pending/published/rejected/archived');
        });
    }

    public function down(): void
    {
        Schema::table('skill_details', function (Blueprint $table): void {
            $table->dropColumn('status');
        });
    }
};

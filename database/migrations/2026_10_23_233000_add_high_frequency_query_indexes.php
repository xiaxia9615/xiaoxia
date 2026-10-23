<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // api_calls：用户最近调用列表 + 产品调用统计（created_at 倒序）
        Schema::table('api_calls', function (Blueprint $table): void {
            $table->index(['user_id', 'created_at'], 'idx_api_calls_user_created');
            $table->index(['product_id', 'created_at'], 'idx_api_calls_product_created');
        });

        // ledger_entries：按业务维度（biz_type/biz_id）查询流水，带时间倒序
        Schema::table('ledger_entries', function (Blueprint $table): void {
            $table->index(['biz_type', 'biz_id', 'created_at'], 'idx_ledger_entries_biz_created');
        });

        // team_billing_records：按团队 / 实际调用人查询计费记录，带时间倒序
        Schema::table('team_billing_records', function (Blueprint $table): void {
            $table->index(['team_id', 'created_at'], 'idx_team_billing_team_created');
            $table->index(['actor_user_id', 'created_at'], 'idx_team_billing_actor_created');
        });
    }

    public function down(): void
    {
        Schema::table('api_calls', function (Blueprint $table): void {
            $table->dropIndex('idx_api_calls_user_created');
            $table->dropIndex('idx_api_calls_product_created');
        });

        Schema::table('ledger_entries', function (Blueprint $table): void {
            $table->dropIndex('idx_ledger_entries_biz_created');
        });

        Schema::table('team_billing_records', function (Blueprint $table): void {
            $table->dropIndex('idx_team_billing_team_created');
            $table->dropIndex('idx_team_billing_actor_created');
        });
    }
};

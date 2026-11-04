<?php

declare(strict_types=1);

namespace App\Modules\Wallet\Database\Migrations;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wallet_transactions', function (Blueprint $table): void {
            // 商户订单号（幂等键）：同一钱包下唯一，数据库唯一索引兜底防重复充值
            $table->string('out_trade_no', 64)->nullable()->after('id');
            $table->unique(['wallet_id', 'out_trade_no'], 'uniq_wallet_out_trade_no');
        });
    }

    public function down(): void
    {
        Schema::table('wallet_transactions', function (Blueprint $table): void {
            $table->dropUnique('uniq_wallet_out_trade_no');
            $table->dropColumn('out_trade_no');
        });
    }
};

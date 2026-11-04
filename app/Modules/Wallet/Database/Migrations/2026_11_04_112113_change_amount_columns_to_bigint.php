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
        // 金额字段统一为整数（毫，1 元 = 1000 毫），与 Ledger 账本单位绝对一致。
        // 金融级铁律：金额严禁使用浮点数/小数存储与计算。
        Schema::table('wallet_transactions', function (Blueprint $table): void {
            $table->unsignedBigInteger('amount')->default(0)->change();
        });

        Schema::table('wallets', function (Blueprint $table): void {
            $table->unsignedBigInteger('balance')->default(0)->change();
            $table->unsignedBigInteger('frozen_balance')->default(0)->change();
        });
    }

    public function down(): void
    {
        Schema::table('wallet_transactions', function (Blueprint $table): void {
            $table->decimal('amount', 12, 2)->default(0)->change();
        });

        Schema::table('wallets', function (Blueprint $table): void {
            $table->decimal('balance', 12, 2)->default(0)->change();
            $table->decimal('frozen_balance', 12, 2)->default(0)->change();
        });
    }
};

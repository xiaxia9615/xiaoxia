<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ledger_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('owner_type', 32);
            $table->unsignedBigInteger('owner_id');
            $table->string('account_type', 32);
            $table->string('currency', 3)->default('CNY');
            $table->timestamps();
            $table->unique(['owner_type', 'owner_id', 'account_type', 'currency'], 'uniq_account');
        });

        Schema::create('ledger_entries', function (Blueprint $table) {
            $table->id();
            $table->string('transaction_id', 36)->index();
            $table->unsignedBigInteger('account_id');
            $table->string('direction', 8);
            $table->bigInteger('amount');
            $table->string('biz_type', 32);
            $table->unsignedBigInteger('biz_id');
            $table->timestamps();
            $table->index(['account_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ledger_entries');
        Schema::dropIfExists('ledger_accounts');
    }
};

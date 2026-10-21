<?php

declare(strict_types=1);

namespace App\Modules\Team\Database\Migrations;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('team_billing_records', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('team_id')->index()->comment('团队ID');
            $table->unsignedBigInteger('actor_user_id')->index()->comment('实际发起调用的员工ID');
            $table->unsignedBigInteger('billing_owner_id')->comment('扣费主体（团队owner）ID');
            $table->unsignedBigInteger('api_key_id')->comment('团队共享API Key ID');
            $table->string('request_id', 36)->unique()->comment('幂等键');
            $table->unsignedBigInteger('amount')->comment('扣费金额（单位：毫）');
            $table->string('ledger_transaction_id', 36)->comment('Ledger复式记账事务ID');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('team_billing_records');
    }
};

<?php

declare(strict_types=1);

namespace App\Modules\Order\Database\Migrations;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('tracking_no', 64)->nullable()->comment('物流单号（实物订单发货后填写）')->after('paid_at');
            $table->timestamp('shipped_at')->nullable()->comment('发货时间')->after('tracking_no');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['tracking_no', 'shipped_at']);
        });
    }
};

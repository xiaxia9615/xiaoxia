<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('addresses', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id')->index()->comment('所属用户');
            $table->string('name', 50)->comment('收货人姓名');
            $table->string('phone', 20)->comment('联系电话');
            $table->string('province', 50)->default('')->comment('省份');
            $table->string('city', 50)->default('')->comment('城市');
            $table->string('district', 50)->default('')->comment('区县');
            $table->string('detail')->comment('详细地址');
            $table->boolean('is_default')->default(false)->index()->comment('是否默认地址');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('addresses');
    }
};

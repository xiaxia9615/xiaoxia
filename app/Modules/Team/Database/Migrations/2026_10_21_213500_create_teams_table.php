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
        Schema::create('teams', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_id')->constrained('users')->cascadeOnDelete()->comment('创建者');
            $table->string('name', 100)->comment('团队名称');
            $table->timestamps();
            $table->index('owner_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('teams');
    }
};

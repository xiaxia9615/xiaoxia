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
        Schema::create('team_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained('teams')->cascadeOnDelete()->comment('团队ID');
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete()->comment('成员ID');
            $table->string('role', 20)->default('member')->comment('角色：owner/admin/member');
            $table->timestamps();
            $table->unique(['team_id', 'user_id']);
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('team_user');
    }
};

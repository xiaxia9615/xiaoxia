<?php
declare(strict_types=1);
namespace App\Modules\AiProvider\Database\Migrations;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ai_providers', function (Blueprint $table) {
            $table->decimal('cost_per_1k_input', 12, 6)->default(0)->after('base_url')->comment('每千 token 输入成本（元）');
            $table->decimal('cost_per_1k_output', 12, 6)->default(0)->after('cost_per_1k_input')->comment('每千 token 输出成本（元）');
        });
    }
    public function down(): void
    {
        Schema::table('ai_providers', function (Blueprint $table) {
            $table->dropColumn(['cost_per_1k_input', 'cost_per_1k_output']);
        });
    }
};

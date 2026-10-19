<?php
declare(strict_types=1);
namespace App\Modules\Ledger\Listeners;
use App\Modules\Ledger\Events\AiUsageRecorded;
use Illuminate\Support\Facades\DB;
final readonly class DeductBalanceOnAiUsage
{
    public function handle(AiUsageRecorded $event): void
    {
        DB::table('ai_usage_deductions')->insert([
            'user_id'       => $event->userId,
            'provider_id'   => $event->providerId,
            'cost'          => $event->cost,
            'input_tokens'  => $event->inputTokens,
            'output_tokens' => $event->outputTokens,
            'created_at'    => now(),
            'updated_at'    => now(),
        ]);
    }
}

<?php

declare(strict_types=1);

namespace App\Modules\System\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

final class AuditService
{
    public function log(
        int $operatorId,
        string $action,
        ?string $targetType = null,
        ?int $targetId = null,
        array $payload = [],
    ): void {
        try {
            DB::table('audit_logs')->insert([
                'operator_id' => $operatorId,
                'action' => $action,
                'target_type' => $targetType,
                'target_id' => $targetId,
                'payload' => json_encode($payload),
                'ip' => request()?->ip(),
                'user_agent' => request()?->userAgent(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (\Throwable $e) {
            Log::warning('audit_log_write_failed', [
                'action' => $action,
                'error' => $e->getMessage(),
            ]);
        }
    }
}

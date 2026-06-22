<?php

namespace Modules\TripManagement\Http\Controllers\Concerns;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

trait LogsVitoAudit
{
    /**
     * Write a row to the vito_audit_log table. Never throws — auditing must
     * never break the main flow.
     */
    protected function auditLog(?string $userId, string $action, string $modelType, string $modelId, array $changes): void
    {
        try {
            DB::table('vito_audit_log')->insert([
                'id'         => Str::uuid(),
                'user_id'    => $userId,
                'action'     => $action,
                'model_type' => $modelType,
                'model_id'   => $modelId,
                'changes'    => json_encode($changes),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (\Throwable $e) {
            Log::warning('Audit log write failed', [
                'user_id'    => $userId,
                'action'     => $action,
                'model_type' => $modelType,
                'model_id'   => $modelId,
                'error'      => $e->getMessage(),
            ]);
        }
    }
}

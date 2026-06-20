<?php

namespace App\Services\Coolify;

use App\Models\CoolifyBackupAuditLog;
use Illuminate\Support\Facades\Auth;

class CoolifyBackupAuditService
{
    /**
     * @param  array<string, mixed>  $metadata
     */
    public function log(
        string $action,
        ?string $subjectType = null,
        ?string $subjectUuid = null,
        ?string $resourceType = null,
        ?string $resourceUuid = null,
        string $status = 'completed',
        ?string $message = null,
        array $metadata = []
    ): CoolifyBackupAuditLog {
        return CoolifyBackupAuditLog::create([
            'action' => $action,
            'subject_type' => $subjectType,
            'subject_uuid' => $subjectUuid,
            'resource_type' => $resourceType,
            'resource_uuid' => $resourceUuid,
            'user_id' => Auth::id(),
            'status' => $status,
            'message' => $message,
            'metadata' => $metadata === [] ? null : $metadata,
        ]);
    }
}

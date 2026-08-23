<?php

namespace App\Support;

use App\Models\AuditEvent;
use Illuminate\Support\Facades\Auth;

class AuditLogger
{
    public static function log(string $action, string $subjectType, ?int $subjectId, array $old = [], array $new = [], ?string $reason = null): AuditEvent
    {
        $previousHash = AuditEvent::query()->latest('id')->value('hash');

        $payload = [
            'tenant_id' => TenantContext::id(),
            'user_id' => Auth::id(),
            'user_role' => Auth::user()?->primaryRoleLabel(),
            'action' => $action,
            'subject_type' => $subjectType,
            'subject_id' => $subjectId,
            'old_values' => $old,
            'new_values' => $new,
            'reason' => $reason,
            'ip_address' => request()?->ip(),
            'session_id' => session()->getId(),
            'previous_hash' => $previousHash,
            'created_at' => now(),
        ];

        $payload['hash'] = hash('sha256', $previousHash.json_encode($payload));

        return AuditEvent::create($payload);
    }
}

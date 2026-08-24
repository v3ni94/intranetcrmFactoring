<?php

namespace App\Support;

use App\Models\AuditEvent;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AuditLogger
{
    public static function log(string $action, string $subjectType, ?int $subjectId, array $old = [], array $new = [], ?string $reason = null): AuditEvent
    {
        // Transaktion + Zeilensperre auf dem letzten Eintrag: zwei parallele Writes
        // wuerden sonst denselben previous_hash lesen und die Kette forken.
        return DB::transaction(function () use ($action, $subjectType, $subjectId, $old, $new, $reason) {
            $previousHash = AuditEvent::query()->lockForUpdate()->latest('id')->value('hash');

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
        });
    }

    /**
     * Prueft die gesamte Hash-Kette (append-only-Nachweis). Liefert die IDs der
     * Eintraege, deren Verkettung nicht zum Vorgaenger passt (leer = Kette intakt).
     *
     * @return array<int, int>
     */
    public static function verifyChain(): array
    {
        $broken = [];
        $previousHash = null;

        AuditEvent::query()->orderBy('id')->chunk(500, function ($events) use (&$broken, &$previousHash) {
            foreach ($events as $event) {
                if ($event->previous_hash !== $previousHash) {
                    $broken[] = $event->id;
                }
                $previousHash = $event->hash;
            }
        });

        return $broken;
    }
}

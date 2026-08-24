<?php

namespace Tests\Feature;

use App\Models\AuditEvent;
use App\Models\Receivable;
use App\Support\AuditLogger;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Abschnitt 17: append-only Audit-Log mit Hash-Verkettung. Die Kette muss
 * nachtraeglich verifizierbar sein und Manipulationen sichtbar machen.
 */
class AuditChainTest extends TestCase
{
    use RefreshDatabase;

    public function test_hash_chain_is_intact_after_multiple_writes(): void
    {
        foreach (range(1, 5) as $i) {
            AuditLogger::log('test', Receivable::class, $i, [], ['step' => $i]);
        }

        $this->assertSame(5, AuditEvent::count());
        $this->assertSame([], AuditLogger::verifyChain(), 'Die Hash-Kette sollte ohne Bruch verifizierbar sein.');
    }

    public function test_tampering_with_an_event_breaks_the_chain(): void
    {
        foreach (range(1, 3) as $i) {
            AuditLogger::log('test', Receivable::class, $i, [], ['step' => $i]);
        }

        // Nachtraegliche Manipulation direkt in der DB (am Modell vorbei).
        $second = AuditEvent::orderBy('id')->skip(1)->first();
        DB::table('audit_events')->where('id', $second->id)->update(['hash' => str_repeat('0', 64)]);

        $broken = AuditLogger::verifyChain();
        $this->assertNotEmpty($broken, 'Manipulation muss die Kettenpruefung fehlschlagen lassen.');
    }
}

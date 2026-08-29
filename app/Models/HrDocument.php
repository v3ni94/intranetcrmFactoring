<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Nachweis-Dokument in der Personalakte (v3.04): Scan von Personalausweis,
 * Fuehrerschein, SCHUFA-Auskunft, Fuehrungszeugnis oder Sonstigem zu einem
 * Benutzer. Zugriff ausschliesslich ueber die Benutzerverwaltung
 * (Systemadministration, Geschaeftsleitung, Superadmin); die Datei liegt
 * geschuetzt unter storage/app und wird nur ueber die Anwendung ausgeliefert.
 */
class HrDocument extends Model
{
    /** Zulaessige Nachweisarten mit Anzeigenamen. */
    public const TYPES = [
        'personalausweis' => 'Personalausweis',
        'fuehrerschein' => 'Führerschein',
        'schufa' => 'SCHUFA-Auskunft',
        'fuehrungszeugnis' => 'Führungszeugnis',
        'sonstiges' => 'Sonstiges',
    ];

    protected $fillable = [
        'tenant_id', 'user_id', 'doc_type', 'original_name',
        'storage_path', 'mime_type', 'size_bytes', 'uploaded_by',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function typeLabel(): string
    {
        return self::TYPES[$this->doc_type] ?? $this->doc_type;
    }
}

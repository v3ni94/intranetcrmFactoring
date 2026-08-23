<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class Receivable extends Model
{
    use BelongsToTenant;

    public const STATUSES = [
        'entwurf', 'eingereicht', 'formale_pruefung', 'risiko_limitpruefung', 'rueckfrage',
        'freigegeben', 'angekauft', 'zur_auszahlung', 'zahlung_angewiesen', 'ausgezahlt',
        'teilbezahlt', 'bezahlt', 'abgerechnet', 'abgelehnt', 'zurueckgezogen', 'gesperrt',
        'streitig', 'ueberfaellig', 'rueckgriff', 'ausgefallen', 'abgeschrieben', 'wieder_eingezogen',
    ];

    public const STATUS_LABELS = [
        'entwurf' => 'Entwurf',
        'eingereicht' => 'Eingereicht',
        'formale_pruefung' => 'Formale Prüfung',
        'risiko_limitpruefung' => 'Risiko-/Limitprüfung',
        'rueckfrage' => 'Rückfrage',
        'freigegeben' => 'Freigegeben',
        'angekauft' => 'Angekauft',
        'zur_auszahlung' => 'Zur Auszahlung',
        'zahlung_angewiesen' => 'Zahlung angewiesen',
        'ausgezahlt' => 'Ausgezahlt',
        'teilbezahlt' => 'Teilbezahlt',
        'bezahlt' => 'Bezahlt',
        'abgerechnet' => 'Abgerechnet',
        'abgelehnt' => 'Abgelehnt',
        'zurueckgezogen' => 'Zurückgezogen',
        'gesperrt' => 'Gesperrt',
        'streitig' => 'Streitig',
        'ueberfaellig' => 'Überfällig',
        'rueckgriff' => 'Rückgriff',
        'ausgefallen' => 'Ausgefallen',
        'abgeschrieben' => 'Abgeschrieben',
        'wieder_eingezogen' => 'Wieder eingezogen',
    ];

    protected $fillable = [
        'tenant_id', 'receivable_number', 'organization_id', 'contract_id', 'debtor_organization_id',
        'debtor_pseudonym_id', 'invoice_number', 'invoice_date', 'due_date', 'invoice_amount',
        'currency', 'status', 'source_channel', 'rejection_reason', 'triggered_rule',
        'submitted_by', 'reviewed_by', 'is_demo',
    ];

    protected $casts = [
        'invoice_date' => 'date',
        'due_date' => 'date',
        'invoice_amount' => 'decimal:4',
        'is_demo' => 'boolean',
    ];

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function contract()
    {
        return $this->belongsTo(Contract::class);
    }

    public function debtorOrganization()
    {
        return $this->belongsTo(Organization::class, 'debtor_organization_id');
    }

    public function purchase()
    {
        return $this->hasOne(Purchase::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function dunningCases()
    {
        return $this->hasMany(DunningCase::class);
    }

    public function statusLabel(): string
    {
        return self::STATUS_LABELS[$this->status] ?? $this->status;
    }

    public function ageInDays(): int
    {
        return (int) now()->diffInDays($this->due_date, false) * -1;
    }
}

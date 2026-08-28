<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class ReportSubscription extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id', 'recipient_email', 'report_type', 'frequency', 'active', 'last_sent_at', 'created_by',
    ];

    protected $casts = [
        'active' => 'boolean',
        'last_sent_at' => 'datetime',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** Ist der naechste Versand nach Frequenz und letztem Versand faellig? */
    public function isDue(): bool
    {
        if (! $this->active) {
            return false;
        }
        if (! $this->last_sent_at) {
            return true;
        }

        return match ($this->frequency) {
            'taeglich' => $this->last_sent_at->lt(now()->startOfDay()),
            'woechentlich' => $this->last_sent_at->lt(now()->startOfWeek()),
            'monatlich' => $this->last_sent_at->lt(now()->startOfMonth()),
            default => false,
        };
    }
}

<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class Decision extends Model
{
    use BelongsToTenant;

    public const STATUSES = ['Hypothese', 'In Pruefung', 'Externer Rat erforderlich', 'Beschlossen', 'Verworfen', 'Ersetzt'];

    protected $fillable = [
        'tenant_id', 'decision_id', 'title', 'status', 'decision_date', 'participants',
        'preconditions', 'replaces_decision_id', 'owner', 'is_demo',
    ];

    protected $casts = [
        'decision_date' => 'date',
        'is_demo' => 'boolean',
    ];

    public function replaces()
    {
        return $this->belongsTo(Decision::class, 'replaces_decision_id');
    }

    public function isBinding(): bool
    {
        return $this->status === 'Beschlossen';
    }
}

<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class ProjectRisk extends Model
{
    use BelongsToTenant;

    public const LEVELS = ['niedrig', 'mittel', 'hoch'];

    protected $fillable = [
        'tenant_id', 'workstream_id', 'title', 'probability', 'impact', 'mitigation',
        'owner_id', 'due_date', 'status', 'is_demo',
    ];

    protected $casts = [
        'due_date' => 'date',
        'is_demo' => 'boolean',
    ];

    public function workstream()
    {
        return $this->belongsTo(Workstream::class);
    }

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    /** Grobe Score-Heuristik fuer die Sortierung im Risk Log (niedrig=1 .. hoch=3). */
    public function score(): int
    {
        $levelValue = array_flip(self::LEVELS);

        return ($levelValue[$this->probability] + 1) * ($levelValue[$this->impact] + 1);
    }
}

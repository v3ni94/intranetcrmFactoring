<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class OperatingCost extends Model
{
    use BelongsToTenant;

    public const CATEGORIES = [
        'personal' => 'Personal',
        'it' => 'IT & Software',
        'buero' => 'Büro & Verwaltung',
        'versicherung' => 'Versicherungen',
        'refinanzierung' => 'Refinanzierung',
        'beratung' => 'Recht & Beratung',
        'marketing' => 'Marketing & Vertrieb',
        'sonstiges' => 'Sonstiges',
    ];

    protected $fillable = ['tenant_id', 'cost_date', 'category', 'description', 'amount', 'created_by', 'is_demo'];

    protected $casts = [
        'cost_date' => 'date',
        'amount' => 'decimal:4',
        'is_demo' => 'boolean',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}

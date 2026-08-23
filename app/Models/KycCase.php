<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class KycCase extends Model
{
    use BelongsToTenant;

    protected $table = 'kyc_cases';

    protected $fillable = [
        'tenant_id', 'organization_id', 'case_type', 'provider', 'result', 'risk_class',
        'reviewed_at', 'next_review_at', 'reviewed_by', 'notes', 'is_demo',
    ];

    protected $casts = [
        'reviewed_at' => 'date',
        'next_review_at' => 'date',
        'is_demo' => 'boolean',
    ];

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}

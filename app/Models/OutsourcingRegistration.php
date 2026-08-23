<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class OutsourcingRegistration extends Model
{
    use BelongsToTenant;

    protected $table = 'outsourcing_registrations';

    protected $fillable = [
        'tenant_id', 'service', 'provider', 'data_access', 'criticality', 'contract_reference',
        'exit_plan', 'audit_right', 'dora_relevant', 'is_demo',
    ];

    protected $casts = [
        'audit_right' => 'boolean',
        'dora_relevant' => 'boolean',
        'is_demo' => 'boolean',
    ];
}

<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class Contact extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id', 'organization_id', 'salutation', 'first_name', 'last_name',
        'role', 'email', 'phone', 'is_authorized_representative', 'is_demo',
    ];

    protected $casts = [
        'is_authorized_representative' => 'boolean',
        'is_demo' => 'boolean',
    ];

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function fullName(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }
}

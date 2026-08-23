<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class DemoResetLog extends Model
{
    use BelongsToTenant;

    public $timestamps = false;

    protected $fillable = ['tenant_id', 'action', 'performed_by', 'affected_records', 'performed_at'];

    protected $casts = ['performed_at' => 'datetime'];

    public function performer()
    {
        return $this->belongsTo(User::class, 'performed_by');
    }
}

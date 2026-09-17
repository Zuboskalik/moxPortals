<?php

namespace App\Models;

use App\Enums\PortalStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Portal extends Model
{
    use HasFactory;
    use HasUuids;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'name',
        'destination_world',
        'energy_level',
        'stability',
        'time_to_collapse',
        'creatures_count',
        'status',
    ];

    protected $casts = [
        'energy_level' => 'integer',
        'stability' => 'float',
        'time_to_collapse' => 'integer',
        'creatures_count' => 'integer',
        'status' => PortalStatus::class,
    ];

    public function logs(): HasMany
    {
        return $this->hasMany(PortalLog::class);
    }
}

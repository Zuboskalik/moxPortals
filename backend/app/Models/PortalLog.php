<?php

namespace App\Models;

use App\Enums\ActionType;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PortalLog extends Model
{
    use HasUuids;

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = [
        'portal_id',
        'action_type',
        'description',
        'previous_state',
        'new_state',
        'timestamp',
    ];

    protected $casts = [
        'action_type' => ActionType::class,
        'previous_state' => 'array',
        'new_state' => 'array',
        'timestamp' => 'datetime',
    ];

    public function portal(): BelongsTo
    {
        return $this->belongsTo(Portal::class);
    }
}

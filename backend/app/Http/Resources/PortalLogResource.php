<?php

namespace App\Http\Resources;

use App\Models\PortalLog;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin PortalLog */
class PortalLogResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'portal_id' => $this->portal_id,
            'action_type' => $this->action_type->value,
            'description' => $this->description,
            'previous_state' => $this->previous_state,
            'new_state' => $this->new_state,
            'timestamp' => $this->timestamp,
        ];
    }
}

<?php

namespace App\Http\Resources;

use App\Models\Portal;
use App\Services\RiskCalculator;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Portal */
class PortalResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $riskCalculator = app(RiskCalculator::class);

        return [
            'id' => $this->id,
            'name' => $this->name,
            'destination_world' => $this->destination_world,
            'energy_level' => $this->energy_level,
            'stability' => $this->stability,
            'time_to_collapse' => $this->time_to_collapse,
            'creatures_count' => $this->creatures_count,
            'status' => $this->status->value,
            'risk_score' => $riskCalculator->score($this->resource),
            'risk_level' => $riskCalculator->level($this->resource)->value,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}

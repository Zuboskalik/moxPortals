<?php

namespace App\Services;

use App\Enums\RiskLevel;
use App\Models\Portal;

final class RiskCalculator
{
    public function calculateRisk(Portal $portal): int
    {
        $score = $portal->energy_level * 0.4
            + (1 - $portal->stability) * 40
            + ($portal->time_to_collapse < 15 ? 20 : 0);

        return (int) round(min(100, max(0, $score)));
    }

    public function score(Portal $portal): int
    {
        return $this->calculateRisk($portal);
    }

    public function level(Portal $portal): RiskLevel
    {
        $score = $this->calculateRisk($portal);

        return match (true) {
            $score >= 75 => RiskLevel::Critical,
            $score >= 50 => RiskLevel::High,
            $score >= 25 => RiskLevel::Medium,
            default => RiskLevel::Low,
        };
    }
}

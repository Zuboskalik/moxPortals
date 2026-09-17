<?php

namespace Tests\Unit;

use App\Enums\RiskLevel;
use App\Models\Portal;
use App\Services\RiskCalculator;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class RiskCalculatorTest extends TestCase
{
    private RiskCalculator $calculator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->calculator = new RiskCalculator;
    }

    private function makePortal(int $energyLevel, float $stability, int $timeToCollapse): Portal
    {
        return new Portal([
            'name' => 'Test Portal',
            'destination_world' => 'Testland',
            'energy_level' => $energyLevel,
            'stability' => $stability,
            'time_to_collapse' => $timeToCollapse,
            'creatures_count' => 0,
            'status' => 'active',
        ]);
    }

    public static function scoreProvider(): array
    {
        // score = energy_level*0.4 + (1-stability)*40 + (time_to_collapse < 15 ? 20 : 0)
        return [
            'minimal score (0)' => [1, 1.0, 60, 0],
            'score 24 (just below MEDIUM)' => [60, 1.0, 60, 24],
            'score 50 (HIGH boundary)' => [100, 0.75, 60, 50],
            'score capped at 100' => [100, 0.0, 5, 100],
        ];
    }

    #[DataProvider('scoreProvider')]
    public function test_calculates_score_correctly(int $energy, float $stability, int $ttc, int $expectedScore): void
    {
        $portal = $this->makePortal($energy, $stability, $ttc);

        $this->assertSame($expectedScore, $this->calculator->calculateRisk($portal));
    }

    public function test_time_to_collapse_below_15_adds_bonus(): void
    {
        $withBonus = $this->makePortal(50, 0.5, 14);
        $withoutBonus = $this->makePortal(50, 0.5, 15);

        $this->assertSame(
            $this->calculator->calculateRisk($withoutBonus) + 20,
            $this->calculator->calculateRisk($withBonus)
        );
    }

    public function test_time_to_collapse_exactly_15_does_not_get_bonus(): void
    {
        $portal = $this->makePortal(50, 0.5, 15);

        // score = 50*0.4 + 0.5*40 + 0 = 20 + 20 = 40 (без бонуса +20)
        $this->assertSame(40, $this->calculator->calculateRisk($portal));
    }

    public static function levelProvider(): array
    {
        return [
            'score 0 -> LOW' => [1, 1.0, 60, RiskLevel::Low],
            'score 24 -> LOW (just below MEDIUM)' => [60, 1.0, 60, RiskLevel::Low],
            'score 25 -> MEDIUM (exact boundary)' => [60, 0.975, 60, RiskLevel::Medium],
            'score 49 -> MEDIUM (just below HIGH)' => [100, 0.775, 60, RiskLevel::Medium],
            'score 50 -> HIGH (exact boundary)' => [100, 0.75, 60, RiskLevel::High],
            'score 74 -> HIGH (just below CRITICAL)' => [100, 0.15, 60, RiskLevel::High],
            'score 75 -> CRITICAL (exact boundary)' => [100, 0.125, 60, RiskLevel::Critical],
            'score 100 -> CRITICAL' => [100, 0.0, 5, RiskLevel::Critical],
        ];
    }

    #[DataProvider('levelProvider')]
    public function test_maps_score_to_correct_risk_level(int $energy, float $stability, int $ttc, RiskLevel $expected): void
    {
        $portal = $this->makePortal($energy, $stability, $ttc);

        $this->assertSame($expected, $this->calculator->level($portal));
    }
}

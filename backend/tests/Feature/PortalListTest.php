<?php

namespace Tests\Feature;

use App\Models\Portal;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PortalListTest extends TestCase
{
    use RefreshDatabase;

    public function test_returns_empty_list_when_no_portals_exist(): void
    {
        $response = $this->getJson('/api/portals');

        $response->assertOk();
        $response->assertJsonCount(0, 'data');
        $response->assertJsonPath('meta.total', 0);
        $response->assertJsonPath('summary.by_status.active', 0);
        $response->assertJsonPath('summary.by_risk_level.CRITICAL', 0);
        $response->assertJsonPath('summary.avg_risk_score_active', 0);
        $response->assertJsonPath('summary.creatures_total_open', 0);
        $response->assertJsonPath('summary.top_risky_active', []);
    }

    public function test_lists_a_critical_risk_portal_with_correct_risk_fields(): void
    {
        Portal::factory()->critical()->create([
            'name' => 'Врата Пепла',
        ]);

        $response = $this->getJson('/api/portals');

        $response->assertOk();
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.risk_level', 'CRITICAL');
        $response->assertJsonPath('summary.by_risk_level.CRITICAL', 1);
        $this->assertGreaterThanOrEqual(75, $response->json('data.0.risk_score'));
    }

    public function test_filters_portals_by_risk_level_without_affecting_summary(): void
    {
        Portal::factory()->critical()->create();
        Portal::factory()->low()->create();

        $response = $this->getJson('/api/portals?risk_level=CRITICAL');

        $response->assertOk();
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.risk_level', 'CRITICAL');
        // summary всегда считается по полному набору, независимо от фильтра
        $response->assertJsonPath('summary.by_risk_level.CRITICAL', 1);
        $response->assertJsonPath('summary.by_risk_level.LOW', 1);
    }

    public function test_filters_portals_by_status_without_affecting_summary(): void
    {
        Portal::factory()->closed()->create();
        Portal::factory()->create(); // active

        $response = $this->getJson('/api/portals?status=closed');

        $response->assertOk();
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.status', 'closed');
        $response->assertJsonPath('summary.by_status.closed', 1);
        $response->assertJsonPath('summary.by_status.active', 1);
    }

    public function test_dashboard_summary_aggregates_are_computed_correctly(): void
    {
        $critical = Portal::factory()->critical()->create(['creatures_count' => 2]);
        $low = Portal::factory()->low()->create(['creatures_count' => 1]);
        Portal::factory()->closed()->create(['creatures_count' => 5]); // closed -> исключён из creatures_total_open

        $response = $this->getJson('/api/portals');

        $response->assertOk();
        // creatures_total_open суммирует только открытые (не closed) порталы: 2 + 1 = 3
        $response->assertJsonPath('summary.creatures_total_open', 3);
        $response->assertJsonPath('summary.by_status.closed', 1);

        $topRisky = $response->json('summary.top_risky_active');
        $this->assertCount(2, $topRisky); // closed портал не входит в top_risky_active
        $this->assertSame($critical->id, $topRisky[0]['id']);
        $this->assertSame($low->id, $topRisky[1]['id']);
    }

    public function test_returns_404_for_nonexistent_portal(): void
    {
        $response = $this->getJson('/api/portals/00000000-0000-0000-0000-000000000000');

        $response->assertNotFound();
    }
}

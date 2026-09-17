<?php

namespace Tests\Feature;

use App\Models\Portal;
use App\Models\PortalLog;
use App\Services\RiskCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PortalActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_stabilize_reduces_risk_and_updates_portal_state(): void
    {
        // energy=80, stability=0.2, ttc=5 (<15) -> score = 32 + 32 + 20 = 84 (CRITICAL)
        $portal = Portal::factory()->critical()->create([
            'energy_level' => 80,
            'stability' => 0.2,
        ]);
        $initialScore = app(RiskCalculator::class)->calculateRisk($portal);

        $response = $this->postJson("/api/portals/{$portal->id}/action", [
            'action' => 'stabilize',
        ]);

        $response->assertOk();
        $response->assertJsonPath('data.status', 'stabilized');
        $response->assertJsonPath('data.stability', 0.95);
        $response->assertJsonPath('data.energy_level', 72); // floor(80 * 0.9)

        // после stabilize: energy=72, stability=0.95, ttc=5 (<15) -> score = 28.8 + 2 + 20 = 50.8 ≈ 51 (HIGH)
        $response->assertJsonPath('data.risk_score', 51);
        $response->assertJsonPath('data.risk_level', 'HIGH');
        $this->assertLessThan($initialScore, $response->json('data.risk_score'));

        $portal->refresh();
        $this->assertSame('stabilized', $portal->status->value);
        $this->assertEquals(0.95, $portal->stability);
        $this->assertSame(72, $portal->energy_level);
    }

    public function test_stabilize_on_closed_portal_is_forbidden(): void
    {
        $portal = Portal::factory()->closed()->create([
            'energy_level' => 50,
            'stability' => 0.5,
        ]);

        $response = $this->postJson("/api/portals/{$portal->id}/action", [
            'action' => 'stabilize',
        ]);

        $response->assertStatus(422);
        $response->assertJsonPath('error_code', 'PORTAL_ALREADY_CLOSED');

        $portal->refresh();
        $this->assertSame('closed', $portal->status->value);
        $this->assertEquals(0.5, $portal->stability);
        $this->assertDatabaseCount('portal_logs', 0);
    }

    public function test_dispatch_observer_is_forbidden_when_risk_is_critical(): void
    {
        $portal = Portal::factory()->critical()->create([
            'creatures_count' => 0,
        ]);

        $response = $this->postJson("/api/portals/{$portal->id}/action", [
            'action' => 'dispatch_observer',
        ]);

        $response->assertStatus(422);
        $response->assertJsonPath('error_code', 'RISK_TOO_HIGH_FOR_OBSERVER');

        $portal->refresh();
        $this->assertSame(0, $portal->creatures_count);
        $this->assertDatabaseCount('portal_logs', 0);
    }

    public function test_close_succeeds_without_force_evacuate_when_no_creatures_present(): void
    {
        $portal = Portal::factory()->create(['creatures_count' => 0]);

        $response = $this->postJson("/api/portals/{$portal->id}/action", [
            'action' => 'close',
        ]);

        $response->assertOk();
        $response->assertJsonPath('data.status', 'closed');
        $this->assertDatabaseCount('portal_logs', 1);
    }

    public function test_close_without_force_evacuate_is_forbidden_when_creatures_present(): void
    {
        $portal = Portal::factory()->withCreatures(3)->create();

        $response = $this->postJson("/api/portals/{$portal->id}/action", [
            'action' => 'close',
        ]);

        $response->assertStatus(422);
        $response->assertJsonPath('error_code', 'EVACUATION_REQUIRED');

        $portal->refresh();
        $this->assertNotSame('closed', $portal->status->value);
        $this->assertDatabaseCount('portal_logs', 0);
    }

    public function test_dispatch_observer_succeeds_when_risk_is_not_critical(): void
    {
        $portal = Portal::factory()->low()->create(['creatures_count' => 0]);

        $response = $this->postJson("/api/portals/{$portal->id}/action", [
            'action' => 'dispatch_observer',
        ]);

        $response->assertOk();
        $response->assertJsonPath('data.creatures_count', 1);
        $response->assertJsonPath('data.status', 'under_review');

        $portal->refresh();
        $this->assertSame(1, $portal->creatures_count);
        $this->assertSame('under_review', $portal->status->value);
    }

    public function test_close_with_force_evacuate_succeeds_and_records_it_in_log(): void
    {
        $portal = Portal::factory()->withCreatures(3)->create();

        $response = $this->postJson("/api/portals/{$portal->id}/action", [
            'action' => 'close',
            'force_evacuate' => true,
        ]);

        $response->assertOk();
        $response->assertJsonPath('data.status', 'closed');
        $this->assertStringContainsString('принудительной эвакуацией', $response->json('log.description'));
    }

    public function test_every_successful_action_creates_a_portal_log_entry(): void
    {
        $portal = Portal::factory()->create();

        $this->assertDatabaseCount('portal_logs', 0);

        $response = $this->postJson("/api/portals/{$portal->id}/action", [
            'action' => 'mark_under_review',
        ]);

        $response->assertOk();
        $this->assertDatabaseCount('portal_logs', 1);

        $log = PortalLog::query()->first();
        $this->assertSame($portal->id, $log->portal_id);
        $this->assertSame('mark_under_review', $log->action_type->value);
        $this->assertSame('active', $log->previous_state['status']);
        $this->assertSame('under_review', $log->new_state['status']);
    }

    public function test_mark_under_review_changes_only_status_field(): void
    {
        $portal = Portal::factory()->create([
            'energy_level' => 40,
            'stability' => 0.6,
            'time_to_collapse' => 50,
            'creatures_count' => 1,
        ]);

        $response = $this->postJson("/api/portals/{$portal->id}/action", [
            'action' => 'mark_under_review',
        ]);

        $response->assertOk();
        $response->assertJsonPath('data.status', 'under_review');
        $response->assertJsonPath('data.energy_level', 40);
        $response->assertJsonPath('data.stability', 0.6);
        $response->assertJsonPath('data.time_to_collapse', 50);
        $response->assertJsonPath('data.creatures_count', 1);
    }

    public function test_mark_under_review_on_closed_portal_is_forbidden(): void
    {
        $portal = Portal::factory()->closed()->create();

        $response = $this->postJson("/api/portals/{$portal->id}/action", [
            'action' => 'mark_under_review',
        ]);

        $response->assertStatus(422);
        $response->assertJsonPath('error_code', 'PORTAL_ALREADY_CLOSED');
        $this->assertDatabaseCount('portal_logs', 0);
    }

    public function test_rejected_action_does_not_change_portal_snapshot_atomically(): void
    {
        $portal = Portal::factory()->closed()->create([
            'energy_level' => 33,
            'stability' => 0.44,
            'time_to_collapse' => 20,
            'creatures_count' => 2,
        ]);

        $response = $this->postJson("/api/portals/{$portal->id}/action", [
            'action' => 'stabilize',
        ]);

        $response->assertStatus(422);

        $portal->refresh();
        $this->assertSame(33, $portal->energy_level);
        $this->assertEquals(0.44, $portal->stability);
        $this->assertSame(20, $portal->time_to_collapse);
        $this->assertSame(2, $portal->creatures_count);
        $this->assertSame('closed', $portal->status->value);
        $this->assertDatabaseCount('portal_logs', 0);
    }

    public function test_action_with_invalid_action_name_returns_standard_validation_error(): void
    {
        $portal = Portal::factory()->create();

        $response = $this->postJson("/api/portals/{$portal->id}/action", [
            'action' => 'self_destruct',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['action']);
        $response->assertJsonMissing(['error_code' => 'PORTAL_ALREADY_CLOSED']);
    }
}

<?php

namespace App\Services;

use App\Enums\ActionType;
use App\Enums\PortalStatus;
use App\Enums\RiskLevel;
use App\Exceptions\PortalActionException;
use App\Models\Portal;
use App\Models\PortalLog;
use Illuminate\Support\Facades\DB;

final class PortalActionService
{
    public function __construct(
        private readonly RiskCalculator $riskCalculator,
    ) {}

    public function perform(Portal $portal, ActionType $action, bool $forceEvacuate = false): Portal
    {
        return DB::transaction(function () use ($portal, $action, $forceEvacuate) {
            /** @var Portal $portal */
            $portal = Portal::query()->whereKey($portal->id)->lockForUpdate()->firstOrFail();

            $previousState = $this->snapshot($portal);

            $description = match ($action) {
                ActionType::Stabilize => $this->stabilize($portal),
                ActionType::Close => $this->close($portal, $forceEvacuate),
                ActionType::DispatchObserver => $this->dispatchObserver($portal),
                ActionType::MarkUnderReview => $this->markUnderReview($portal),
            };

            $portal->save();
            $portal->refresh();

            PortalLog::query()->create([
                'portal_id' => $portal->id,
                'action_type' => $action,
                'description' => $description,
                'previous_state' => $previousState,
                'new_state' => $this->snapshot($portal),
                'timestamp' => now(),
            ]);

            return $portal;
        });
    }

    private function stabilize(Portal $portal): string
    {
        $this->assertNotClosed($portal);

        $portal->stability = 0.95;
        $portal->energy_level = (int) floor($portal->energy_level * 0.9);
        $portal->status = PortalStatus::Stabilized;

        return 'Портал стабилизирован.';
    }

    private function close(Portal $portal, bool $forceEvacuate): string
    {
        if ($portal->creatures_count > 0 && ! $forceEvacuate) {
            throw PortalActionException::evacuationRequired($portal->id, $portal->creatures_count);
        }

        $portal->status = PortalStatus::Closed;

        if ($portal->creatures_count > 0 && $forceEvacuate) {
            return "Портал закрыт с принудительной эвакуацией ({$portal->creatures_count} существ).";
        }

        return 'Портал закрыт.';
    }

    private function dispatchObserver(Portal $portal): string
    {
        if ($this->riskCalculator->level($portal) === RiskLevel::Critical) {
            throw PortalActionException::riskTooHighForObserver($portal->id, $this->riskCalculator->score($portal));
        }

        $portal->creatures_count += 1;
        $portal->status = PortalStatus::UnderReview;

        return 'Наблюдатель отправлен в портал.';
    }

    private function markUnderReview(Portal $portal): string
    {
        $this->assertNotClosed($portal);

        $portal->status = PortalStatus::UnderReview;

        return 'Портал переведён под наблюдение.';
    }

    private function assertNotClosed(Portal $portal): void
    {
        if ($portal->status === PortalStatus::Closed) {
            throw PortalActionException::portalAlreadyClosed($portal->id);
        }
    }

    private function snapshot(Portal $portal): array
    {
        return [
            'id' => $portal->id,
            'name' => $portal->name,
            'destination_world' => $portal->destination_world,
            'energy_level' => $portal->energy_level,
            'stability' => $portal->stability,
            'time_to_collapse' => $portal->time_to_collapse,
            'creatures_count' => $portal->creatures_count,
            'status' => $portal->status->value,
            'risk_score' => $this->riskCalculator->score($portal),
            'risk_level' => $this->riskCalculator->level($portal)->value,
        ];
    }
}

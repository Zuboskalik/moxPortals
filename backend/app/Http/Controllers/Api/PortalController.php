<?php

namespace App\Http\Controllers\Api;

use App\Enums\PortalStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\PortalResource;
use App\Models\Portal;
use App\Models\PortalLog;
use App\Services\RiskCalculator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class PortalController extends Controller
{
    public function __construct(
        private readonly RiskCalculator $riskCalculator,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $enriched = Portal::all()->map(fn (Portal $portal) => [
            'portal' => $portal,
            'risk_score' => $this->riskCalculator->score($portal),
            'risk_level' => $this->riskCalculator->level($portal),
        ]);

        $summary = $this->buildSummary($enriched);

        $filtered = $enriched;

        if ($status = $request->query('status')) {
            $filtered = $filtered->filter(fn (array $entry) => $entry['portal']->status->value === $status);
        }

        if ($riskLevel = $request->query('risk_level')) {
            $filtered = $filtered->filter(fn (array $entry) => $entry['risk_level']->value === strtoupper($riskLevel));
        }

        $filtered = $this->applySort($filtered, $request->query('sort', '-risk_score'))->values();

        $perPage = max(1, (int) $request->query('per_page', 20));
        $page = max(1, (int) $request->query('page', 1));
        $total = $filtered->count();
        $paged = $filtered->slice(($page - 1) * $perPage, $perPage)->values();

        return response()->json([
            'data' => PortalResource::collection($paged->pluck('portal')),
            'meta' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total' => $total,
            ],
            'summary' => $summary,
        ]);
    }

    public function show(Portal $portal): PortalResource
    {
        return new PortalResource($portal);
    }

    private function applySort(Collection $entries, string $sort): Collection
    {
        $descending = str_starts_with($sort, '-');
        $field = ltrim($sort, '-');

        $sorted = match ($field) {
            'name' => $entries->sortBy(fn (array $entry) => $entry['portal']->name),
            'time_to_collapse' => $entries->sortBy(fn (array $entry) => $entry['portal']->time_to_collapse),
            default => $entries->sortBy(fn (array $entry) => $entry['risk_score']),
        };

        return $descending ? $sorted->reverse() : $sorted;
    }

    private function buildSummary(Collection $enriched): array
    {
        $byStatus = [];
        foreach (PortalStatus::cases() as $status) {
            $byStatus[$status->value] = $enriched
                ->filter(fn (array $entry) => $entry['portal']->status === $status)
                ->count();
        }

        $byRiskLevel = [];
        foreach (['CRITICAL', 'HIGH', 'MEDIUM', 'LOW'] as $level) {
            $byRiskLevel[$level] = $enriched
                ->filter(fn (array $entry) => $entry['risk_level']->value === $level)
                ->count();
        }

        $activeEntries = $enriched->filter(
            fn (array $entry) => in_array($entry['portal']->status, [PortalStatus::Active, PortalStatus::UnderReview], true)
        );

        $openEntries = $enriched->filter(
            fn (array $entry) => $entry['portal']->status !== PortalStatus::Closed
        );

        $topRisky = $activeEntries
            ->sortByDesc(fn (array $entry) => $entry['risk_score'])
            ->take(5)
            ->pluck('portal');

        return [
            'by_status' => $byStatus,
            'by_risk_level' => $byRiskLevel,
            'avg_risk_score_active' => $activeEntries->isEmpty()
                ? 0
                : round($activeEntries->avg(fn (array $entry) => $entry['risk_score']), 1),
            'creatures_total_open' => $openEntries->sum(fn (array $entry) => $entry['portal']->creatures_count),
            'top_risky_active' => PortalResource::collection($topRisky)->resolve(),
            'logs_last_24h' => PortalLog::query()->where('timestamp', '>=', now()->subDay())->count(),
        ];
    }
}

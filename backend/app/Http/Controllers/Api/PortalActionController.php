<?php

namespace App\Http\Controllers\Api;

use App\Enums\ActionType;
use App\Http\Controllers\Controller;
use App\Http\Requests\PerformPortalActionRequest;
use App\Http\Resources\PortalLogResource;
use App\Http\Resources\PortalResource;
use App\Models\Portal;
use App\Services\PortalActionService;
use Illuminate\Http\JsonResponse;

class PortalActionController extends Controller
{
    public function __construct(
        private readonly PortalActionService $portalActionService,
    ) {}

    public function perform(PerformPortalActionRequest $request, Portal $portal): JsonResponse
    {
        $action = ActionType::from($request->validated('action'));
        $forceEvacuate = (bool) $request->validated('force_evacuate', false);

        $portal = $this->portalActionService->perform($portal, $action, $forceEvacuate);

        return response()->json([
            'data' => new PortalResource($portal),
            'log' => new PortalLogResource($portal->logs()->latest('timestamp')->first()),
        ]);
    }
}

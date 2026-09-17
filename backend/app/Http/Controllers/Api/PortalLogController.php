<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\PortalLogResource;
use App\Models\PortalLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PortalLogController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = PortalLog::query()->orderByDesc('timestamp');

        if ($portalId = $request->query('portal_id')) {
            $query->where('portal_id', $portalId);
        }

        if ($actionType = $request->query('action_type')) {
            $query->where('action_type', $actionType);
        }

        $perPage = max(1, (int) $request->query('per_page', 20));
        $paginator = $query->paginate($perPage)->withQueryString();

        return response()->json([
            'data' => PortalLogResource::collection($paginator->items()),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Portal;
use App\Models\PortalLog;
use Database\Seeders\DemoPortalSeeder;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class DemoSeedController extends Controller
{
    public function run(): JsonResponse
    {
        abort_unless(app()->environment(['local', 'testing']), 403, 'Недоступно в этом окружении.');

        DB::transaction(function () {
            PortalLog::query()->delete();
            Portal::query()->delete();

            (new DemoPortalSeeder)->run();
        });

        return response()->json([
            'message' => 'Демо-данные пересозданы.',
            'portals_created' => Portal::query()->count(),
        ]);
    }
}

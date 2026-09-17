<?php

use App\Http\Controllers\Api\PortalActionController;
use App\Http\Controllers\Api\PortalController;
use App\Http\Controllers\Api\PortalLogController;
use Illuminate\Support\Facades\Route;

Route::get('/portals', [PortalController::class, 'index']);
Route::get('/portals/{portal}', [PortalController::class, 'show']);
Route::post('/portals/{portal}/action', [PortalActionController::class, 'perform']);

Route::get('/logs', [PortalLogController::class, 'index']);

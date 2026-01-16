<?php

use App\Http\Controllers\Api\DeploymentController;
use App\Http\Controllers\Api\LogIngestController;
use Illuminate\Support\Facades\Route;

Route::middleware('api.key')->group(function () {
    Route::post('/logs', [LogIngestController::class, 'ingest']);
    Route::post('/deployments', [DeploymentController::class, 'store']);
});

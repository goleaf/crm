<?php

declare(strict_types=1);

use App\Http\Controllers\Api\WebLeadController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::middleware('auth:sanctum')->group(function (): void {
    Route::post('/leads/web', [WebLeadController::class, 'store']);
});

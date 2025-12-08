<?php

declare(strict_types=1);

use App\Http\Controllers\Api\ContactController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum'])->group(function (): void {
    // Contact API endpoints with Precognition support
    Route::apiResource('contacts', ContactController::class);
});

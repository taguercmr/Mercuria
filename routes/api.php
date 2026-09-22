<?php

use App\Http\Controllers\Api\V1\MercurialeApiController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->middleware(['throttle:mercuriale-api', 'api.key'])->group(function () {
    Route::get('/mercuriale/rubriques', [MercurialeApiController::class, 'rubriques']);
    Route::get('/mercuriale/articles', [MercurialeApiController::class, 'articles']);
    Route::get('/mercuriale/articles/{reference}', [MercurialeApiController::class, 'show']);
});

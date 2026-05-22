<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\GateController;
use App\Http\Controllers\TestController;
use Illuminate\Support\Facades\Route;

Route::post('/auth/login', [AuthController::class, 'login']);
Route::post('/auth/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');

Route::middleware('auth:sanctum')->group(function () {
	Route::post('/gate/open', [GateController::class, 'open']);
	Route::post('/gate/close', [GateController::class, 'close']);
    Route::get('/profile', [ProfileController::class, 'show']);
	Route::patch('/profile', [ProfileController::class, 'update']);
});

Route::post('/test', [TestController::class, 'index']);


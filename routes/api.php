<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\GateController;
use App\Http\Controllers\AccessLogController;
use App\Http\Controllers\ParkingQuotaController;
use App\Http\Controllers\CctvController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/register', [AuthController::class, 'register']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
    });
});


Route::middleware('auth:sanctum')->group(function () {
    Route::prefix('gate')->group(function () {
        Route::get('/main', [GateController::class, 'getMain']);

        Route::middleware('role:admin,staff')->group(function () {
            Route::post('/open', [GateController::class, 'open']);
            Route::post('/close', [GateController::class, 'close']);
        });

        Route::middleware('role:mahasiswa')->group(function () {
            Route::post('/entry', [GateController::class, 'entry']);
            Route::post('/exit', [GateController::class, 'exit']);
        });
    });

    Route::prefix('profile')->group(function () {
        Route::get('/', [ProfileController::class, 'show']);
        Route::patch('/', [ProfileController::class, 'update']);
        Route::patch('/password', [ProfileController::class, 'updatePassword']);
    });

    Route::prefix('parking-quota')->group(function () {
        Route::get('/', [ParkingQuotaController::class, 'show']);
        Route::patch('/', [ParkingQuotaController::class, 'update'])->middleware('role:admin,staff');
    });

    Route::prefix("cctv")->group(function () {
        Route::get('/main', [CctvController::class, 'getMain']);
    });

    // User management (admin/staff only)
    Route::middleware('role:admin,staff')->group(function () {
        Route::apiResource('users', UserController::class);
        Route::get('/users/{user}/ktm', [UserController::class, 'previewKtm'])
        ->name('users.ktm.preview');
    });

    Route::prefix('access-logs')->group(function () {
        Route::get('/', [AccessLogController::class, 'index'])->middleware('role:admin,staff');
        Route::get('/last-opened', [AccessLogController::class, 'lastOpened']);
    });
});

Route::post('/test', [TestController::class, 'index']);
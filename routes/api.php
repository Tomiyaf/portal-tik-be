<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\GateController;
use App\Http\Controllers\AccessLogController;
use App\Http\Controllers\ParkingQuotaController;
use App\Http\Controllers\CCTVController;
use App\Http\Controllers\IotDeviceController;
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

        Route::middleware('role:admin')->group(function () {
            Route::patch('/', [GateController::class, 'update']);
        });

        Route::middleware('role:admin,staff')->group(function () {
            Route::post('/open', [GateController::class, 'open']);
            Route::post('/close', [GateController::class, 'close']);
        });

        Route::middleware('role:mahasiswa')->group(function () {
            Route::post('/entry', [GateController::class, 'entry']);
            Route::post('/exit', [GateController::class, 'exit']);
            Route::post('/access', [GateController::class, 'access']);
        });
    });

    Route::prefix('profile')->group(function () {
        Route::get('/', [ProfileController::class, 'show']);
        Route::patch('/', [ProfileController::class, 'update']);
        Route::patch('/password', [ProfileController::class, 'updatePassword']);
    });

    Route::prefix('parking-quota')->group(function () {
        Route::get('/', [ParkingQuotaController::class, 'show']);
        Route::patch('/', [ParkingQuotaController::class, 'update'])->middleware('role:admin');
    });

    Route::prefix("cctv")->group(function () {
        Route::middleware('role:admin,staff')->group(function () {
            Route::get('/', [CCTVController::class, 'index']);
            Route::get('/main', [CCTVController::class, 'getMain']);
            Route::get('/{cctv}', [CCTVController::class, 'show']);
        });
        Route::middleware('role:admin')->group(function () {
            Route::post('/', [CCTVController::class, 'store']);
            Route::patch('/{cctv}', [CCTVController::class, 'update']);
            Route::delete('/{cctv}', [CCTVController::class, 'destroy']);
        });
    });

    Route::prefix('users')->group(function () {
        Route::middleware('role:admin,staff')->group(function () {
            Route::get('/', [UserController::class, 'index']);
            Route::get('/{user}', [UserController::class, 'show']);
            Route::get('/{user}/ktm', [UserController::class, 'previewKtm'])->name('users.ktm.preview');
        });
        Route::middleware('role:admin')->group(function () {
            Route::post('/', [UserController::class, 'store']);
            Route::put('/{user}', [UserController::class, 'update']);
            Route::patch('/{user}', [UserController::class, 'update']);
            Route::delete('/{user}', [UserController::class, 'destroy']);
        });
    });

    Route::prefix('access-logs')->group(function () {
        Route::get('/', [AccessLogController::class, 'index'])->middleware('role:admin,staff');
        Route::get('/last-opened', [AccessLogController::class, 'lastOpened'])->middleware('role:admin,staff');
    });

    Route::prefix('iot-device')->group(function () {
        Route::get('/', [IotDeviceController::class, 'show']);
    });
});
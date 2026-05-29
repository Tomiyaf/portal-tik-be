<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\IotDevice;

class IotDeviceController extends Controller
{
    public function show(): JsonResponse
    {
        $iotDevice = IotDevice::first();

        return response()->json([
            'success' => true,
            'data'    => $iotDevice,
        ]);
    }
}

<?php

namespace App\Http\Controllers;

use App\Services\MqttService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GateController extends Controller
{
    public function open(Request $request, MqttService $mqtt): JsonResponse
    {
        $mqtt->publish(config('mqtt.topic_control'), 'open');

        return response()->json([
            'success' => true,
            'action' => 'open',
        ]);
    }

    public function close(Request $request, MqttService $mqtt): JsonResponse
    {
        $mqtt->publish(config('mqtt.topic_control'), 'close');

        return response()->json([
            'success' => true,
            'action' => 'close',
        ]);
    }
}

<?php

namespace App\Http\Controllers;

use App\Jobs\FailAccessLogAfterTimeout;
use App\Models\AccessLog;
use App\Services\MqttService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GateController extends Controller
{
    public function open(Request $request, MqttService $mqtt): JsonResponse
    {
        $validated = $request->validate([
            'gate_id' => ['required', 'integer', 'exists:gates,id'],
            'access_method' => ['required', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ]);

        $accessLog = AccessLog::create([
            'user_id' => $request->user()->id,
            'gate_id' => $validated['gate_id'],
            'access_status' => 'pending',
            'access_method' => $validated['access_method'],
            'notes' => $validated['notes'] ?? null,
        ]);

        $payload = json_encode([
            'access_log_id' => $accessLog->id,
            'action' => 'open',
        ]);

        $mqtt->publish(config('mqtt.topic_control'), $payload);
        FailAccessLogAfterTimeout::dispatch($accessLog->id)->delay(now()->addSeconds(5));

        return response()->json([
            'success' => true,
            'data' => [
                'action' => 'open',
                'opened_at' => now(),
                'access_log_id' => $accessLog->id,
            ],
        ]);
    }

    public function close(Request $request, MqttService $mqtt): JsonResponse
    {
        $validated = $request->validate([
            'gate_id' => ['required', 'integer', 'exists:gates,id'],
            'access_method' => ['required', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ]);

        $accessLog = AccessLog::create([
            'user_id' => $request->user()->id,
            'gate_id' => $validated['gate_id'],
            'access_status' => 'pending',
            'access_method' => $validated['access_method'],
            'notes' => $validated['notes'] ?? null,
        ]);

        $payload = json_encode([
            'access_log_id' => $accessLog->id,
            'action' => 'close',
        ]);

        $mqtt->publish(config('mqtt.topic_control'), $payload);
        FailAccessLogAfterTimeout::dispatch($accessLog->id)->delay(now()->addSeconds(5));

        return response()->json([
            'success' => true,
            'data' => [
                'action' => 'close',
                'closed_at' => now(),
                'access_log_id' => $accessLog->id,
            ],
        ]);
    }
}

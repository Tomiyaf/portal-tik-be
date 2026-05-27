<?php

namespace App\Http\Controllers;

use App\Jobs\FailAccessLogAfterTimeout;
use App\Models\Gate;
use App\Models\AccessLog;
use App\Models\ParkingQuota;
use App\Services\MqttService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class GateController extends Controller
{
    private function calculateDistance(
        float $lat1,
        float $lon1,
        float $lat2,
        float $lon2
    ): float {
        $earthRadius = 6371000;

        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a =
            sin($dLat / 2) * sin($dLat / 2) +
            cos(deg2rad($lat1)) *
            cos(deg2rad($lat2)) *
            sin($dLon / 2) *
            sin($dLon / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }

    public function open(Request $request, MqttService $mqtt): JsonResponse
    {
        $validated = $request->validate([
            'gate_id' => ['required', 'integer', 'exists:gates,id'],
            'access_method' => ['required', Rule::in(['mobile', 'web'])],
            'notes' => ['nullable', 'string'],
        ]);

        $accessLog = AccessLog::create([
            'user_id' => $request->user()->id,
            'gate_id' => $validated['gate_id'],
            'access_status' => 'pending',
            'access_method' => $validated['access_method'],
            'action' => 'open',
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
            'access_method' => ['required', Rule::in(['mobile', 'web'])],
            'notes' => ['nullable', 'string'],
        ]);

        $accessLog = AccessLog::create([
            'user_id' => $request->user()->id,
            'gate_id' => $validated['gate_id'],
            'access_status' => 'pending',
            'access_method' => $validated['access_method'],
            'action' => 'close',
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

    public function entry(Request $request, MqttService $mqtt): JsonResponse
    {
        $validated = $request->validate([
            'gate_id' => ['required', 'integer', 'exists:gates,id'],
            'access_method' => ['required', Rule::in(['mobile', 'web'])],
            'notes' => ['nullable', 'string'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
        ]);

        $gate = Gate::findOrFail($validated['gate_id']);
        $distance = $this->calculateDistance(
            $validated['latitude'],
            $validated['longitude'],
            $gate->latitude,
            $gate->longitude
        );

        if ($distance > $gate->allowed_radius_meter) {
            AccessLog::create([
                'user_id' => $request->user()->id,
                'gate_id' => $validated['gate_id'],
                'access_status' => 'failed',
                'access_method' => $validated['access_method'],
                'action' => 'entry',
                'notes' => 'User is outside the allowed gate radius. Access denied.',
            ]);

            return response()->json([
                'success' => false,
                'message' => 'You are outside the allowed gate radius.',
            ], 403);
        }

        $parkingQuota = ParkingQuota::first();
        $availableSlots = $parkingQuota->total_slots - $parkingQuota->used_slots;

        if ($availableSlots <= 0) {
            AccessLog::create([
                'user_id' => $request->user()->id,
                'gate_id' => $validated['gate_id'],
                'access_status' => 'failed',
                'access_method' => $validated['access_method'],
                'action' => 'entry',
                'notes' => 'Parking quota is full. Access denied.',
            ]);
            
            return response()->json([
                'success' => false,
                'data' => null,
                'message' => 'Parking quota is full. Access denied.',
            ], 403);
        }

        $accessLog = AccessLog::create([
            'user_id' => $request->user()->id,
            'gate_id' => $validated['gate_id'],
            'access_status' => 'pending',
            'access_method' => $validated['access_method'],
            'action' => 'entry',
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
                'action' => 'entry',
                'opened_at' => now(),
                'access_log_id' => $accessLog->id,
            ],
        ]);
    }

    public function exit(Request $request, MqttService $mqtt): JsonResponse
    {
        $validated = $request->validate([
            'gate_id' => ['required', 'integer', 'exists:gates,id'],
            'access_method' => ['required', Rule::in(['mobile', 'web'])],
            'notes' => ['nullable', 'string'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
        ]);

        $gate = Gate::findOrFail($validated['gate_id']);
        $distance = $this->calculateDistance(
            $validated['latitude'],
            $validated['longitude'],
            $gate->latitude,
            $gate->longitude
        );

        if ($distance > $gate->allowed_radius_meter) {
            AccessLog::create([
                'user_id' => $request->user()->id,
                'gate_id' => $validated['gate_id'],
                'access_status' => 'failed',
                'access_method' => $validated['access_method'],
                'action' => 'exit',
                'notes' => 'User is outside the allowed gate radius. Access denied.',
            ]);

            return response()->json([
                'success' => false,
                'message' => 'You are outside the allowed gate radius.',
            ], 403);
        }

        $accessLog = AccessLog::create([
            'user_id' => $request->user()->id,
            'gate_id' => $validated['gate_id'],
            'access_status' => 'pending',
            'access_method' => $validated['access_method'],
            'action' => 'exit',
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
                'action' => 'exit',
                'opened_at' => now(),
                'access_log_id' => $accessLog->id,
            ],
        ]);
    }
}

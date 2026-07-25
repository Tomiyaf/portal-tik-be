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

    public function getMain(): JsonResponse
    {
        try {
            $gate = Gate::with('iotDevices')->first();

            return response()->json([
                'success' => true,
                'data' => $gate,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'data' => null,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    private function validateOpenClose(Request $request): array
    {
        return $request->validate([
            'gate_id' => ['required', 'integer', 'exists:gates,id'],
            'access_method' => ['required', Rule::in(['mobile', 'web', 'desktop'])],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);
    }

    private function validateEntryExit(Request $request): array
    {
        return $request->validate([
            'gate_id' => ['required', 'integer', 'exists:gates,id'],
            'access_method' => ['required', Rule::in(['mobile', 'web', 'desktop'])],
            'notes' => ['nullable', 'string', 'max:500'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
        ]);
    }

    private function createAccessLog(array $data): AccessLog
    {
        return AccessLog::create($data);
    }

    private function publishAndQueue(AccessLog $accessLog, MqttService $mqtt, string $action): void
    {
        $payload = json_encode([
            'access_log_id' => $accessLog->id,
            'action' => $action,
        ]);

        $mqtt->publish(config('mqtt.topic_control'), $payload);
        FailAccessLogAfterTimeout::dispatch($accessLog->id)->delay(now()->addSeconds(5));
    }

    private function failWithLog(
        Request $request,
        array $validated,
        string $action,
        string $notes,
        string $message,
        int $status = 403
    ): JsonResponse {
        $this->createAccessLog([
            'user_id' => $request->user()->id,
            'gate_id' => $validated['gate_id'],
            'access_status' => 'failed',
            'access_method' => $validated['access_method'],
            'action' => $action,
            'notes' => $notes,
        ]);

        return response()->json([
            'success' => false,
            'message' => $message,
        ], $status);
    }

    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'gate_name' => 'sometimes|required|string|max:255',
            'latitude' => 'sometimes|required|numeric|between:-90,90',
            'longitude' => 'sometimes|required|numeric|between:-180,180',
            'allowed_radius_meter' => 'sometimes|required|integer|min:1|max:10000',
        ]);

        $gate = Gate::first();
        if (!$gate) {
            return response()->json([
                'success' => false,
                'message' => 'Gate tidak ditemukan.',
            ], 404);
        }

        $gate->update($validated);

        return response()->json([
            'success' => true,
            'data' => $gate,
        ]);
    }

    public function open(Request $request, MqttService $mqtt): JsonResponse
    {
        $validated = $this->validateOpenClose($request);

        $accessLog = $this->createAccessLog([
            'user_id' => $request->user()->id,
            'gate_id' => $validated['gate_id'],
            'access_status' => 'pending',
            'access_method' => $validated['access_method'],
            'action' => 'open',
            'notes' => $validated['notes'] ?? null,
        ]);

        $this->publishAndQueue($accessLog, $mqtt, 'open');

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
            'access_method' => ['required', Rule::in(['mobile', 'web', 'desktop'])],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $accessLog = $this->createAccessLog([
            'user_id' => $request->user()->id,
            'gate_id' => $validated['gate_id'],
            'access_status' => 'pending',
            'access_method' => $validated['access_method'],
            'action' => 'close',
            'notes' => $validated['notes'] ?? null,
        ]);

        $this->publishAndQueue($accessLog, $mqtt, 'close');

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
        $validated = $this->validateEntryExit($request);

        $lastMovement = AccessLog::where('user_id', $request->user()->id)
            ->where('access_status', 'success')
            ->whereIn('action', ['entry', 'exit'])
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->first();

        if ($lastMovement && $lastMovement->action === 'entry') {
            return $this->failWithLog(
                $request,
                $validated,
                'entry',
                'User telah masuk.',
                'Anda telah masuk.'
            );
        }

        $gate = Gate::findOrFail($validated['gate_id']);
        $distance = $this->calculateDistance(
            $validated['latitude'],
            $validated['longitude'],
            $gate->latitude,
            $gate->longitude
        );

        if ($distance > $gate->allowed_radius_meter) {
            return $this->failWithLog(
                $request,
                $validated,
                'entry',
                'User berada di luar radius gate yang diizinkan. Akses ditolak.',
                'Anda berada di luar radius gate yang diizinkan.'
            );
        }

        $parkingQuota = ParkingQuota::first();
        $availableSlots = $parkingQuota->total_slots - $parkingQuota->used_slots;

        if ($availableSlots <= 0 && $parkingQuota->auto_restrict_student) {
            return $this->failWithLog(
                $request,
                $validated,
                'entry',
                'Quota parkir penuh. Akses ditolak.',
                'Quota parkir penuh. Akses ditolak.'
            );
        }

        $accessLog = $this->createAccessLog([
            'user_id' => $request->user()->id,
            'gate_id' => $validated['gate_id'],
            'access_status' => 'pending',
            'access_method' => $validated['access_method'],
            'action' => 'entry',
            'notes' => $validated['notes'] ?? null,
        ]);

        $this->publishAndQueue($accessLog, $mqtt, 'open');

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
        $validated = $this->validateEntryExit($request);

        $lastMovement = AccessLog::where('user_id', $request->user()->id)
            ->where('access_status', 'success')
            ->whereIn('action', ['entry', 'exit'])
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->first();

        if ($lastMovement && $lastMovement->action === 'exit') {
            return $this->failWithLog(
                $request,
                $validated,
                'exit',
                'User telah keluar.',
                'Anda telah keluar.'
            );
        }

        $gate = Gate::findOrFail($validated['gate_id']);
        $distance = $this->calculateDistance(
            $validated['latitude'],
            $validated['longitude'],
            $gate->latitude,
            $gate->longitude
        );

        if ($distance > $gate->allowed_radius_meter) {
            return $this->failWithLog(
                $request,
                $validated,
                'exit',
                'User berada di luar radius gate yang diizinkan. Akses ditolak.',
                'Anda berada di luar radius gate yang diizinkan.'
            );
        }

        $accessLog = $this->createAccessLog([
            'user_id' => $request->user()->id,
            'gate_id' => $validated['gate_id'],
            'access_status' => 'pending',
            'access_method' => $validated['access_method'],
            'action' => 'exit',
            'notes' => $validated['notes'] ?? null,
        ]);

        $this->publishAndQueue($accessLog, $mqtt, 'open');

        return response()->json([
            'success' => true,
            'data' => [
                'action' => 'exit',
                'opened_at' => now(),
                'access_log_id' => $accessLog->id,
            ],
        ]);
    }

    public function access(Request $request, MqttService $mqtt): JsonResponse
    {
        $validated = $this->validateEntryExit($request);

        // $pendingMovement = AccessLog::where('user_id', $request->user()->id)
        //     ->where('access_status', 'pending')
        //     ->whereIn('action', ['entry', 'exit'])
        //     ->latest()
        //     ->first();

        // if ($pendingMovement) {
        //     return response()->json([
        //         'success' => false,
        //         'message' => 'Akses gate sebelumnya masih dalam proses.',
        //     ], 409);
        // }

        $lastMovement = AccessLog::where('user_id', $request->user()->id)
            ->where('access_status', 'success')
            ->whereIn('action', ['entry', 'exit'])
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->first();

        $action = $lastMovement && $lastMovement->action === 'entry'
            ? 'exit'
            : 'entry';

        $gate = Gate::findOrFail($validated['gate_id']);

        $distance = $this->calculateDistance(
            $validated['latitude'],
            $validated['longitude'],
            $gate->latitude,
            $gate->longitude
        );

        if ($distance > $gate->allowed_radius_meter) {
            return $this->failWithLog(
                $request,
                $validated,
                $action,
                'User berada di luar radius gate yang diizinkan. Akses ditolak.',
                'Anda berada di luar radius gate yang diizinkan.'
            );
        }

        if ($action === 'entry') {
            $parkingQuota = ParkingQuota::first();

            $availableSlots = $parkingQuota->total_slots - $parkingQuota->used_slots;

            if ($availableSlots <= 0 && $parkingQuota->auto_restrict_student) {
                return $this->failWithLog(
                    $request,
                    $validated,
                    $action,
                    'Quota parkir penuh. Akses ditolak.',
                    'Quota parkir penuh. Akses ditolak.'
                );
            }
        }

        $accessLog = $this->createAccessLog([
            'user_id' => $request->user()->id,
            'gate_id' => $validated['gate_id'],
            'access_status' => 'pending',
            'access_method' => $validated['access_method'],
            'action' => $action,
            'notes' => $validated['notes'] ?? null,
        ]);

        $this->publishAndQueue($accessLog, $mqtt, 'open');

        return response()->json([
            'success' => true,
            'data' => [
                'action' => $action,
                'opened_at' => now(),
                'access_log_id' => $accessLog->id,
            ],
        ]);
    }
}

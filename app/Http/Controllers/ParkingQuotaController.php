<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ParkingQuota;

class ParkingQuotaController extends Controller
{
    public function show(Request $request)
    {
        $quota = ParkingQuota::first();
        $availableSlots = $quota->total_slots - $quota->used_slots;

        return response()->json([
            'success' => true,
            'data' => [
                'total_slots' => $quota->total_slots,
                'used_slots' => $quota->used_slots,
                'available_slots' => $availableSlots,
                'auto_restrict_student' => $quota->auto_restrict_student,
            ],
        ]);
    }

    public function update(Request $request)
    {
        $quota = ParkingQuota::first();
        $minTotalSlots = $quota ? $quota->used_slots : 0;

        $data = $request->validate([
            'total_slots' => ['sometimes', 'integer', 'min:' . $minTotalSlots],
            'auto_restrict_student' => ['sometimes', 'boolean'],
        ]);

        if ($quota) {
            $quota->update($data);
            $quota->refresh();
        }

        return response()->json([
            'success' => true,
            'data' => $quota,
        ]);
    }
}

<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class AccessLogController extends Controller
{
    public function index(Request $request)
    {
        $accessLogs = $request->user()->accessLogs()->with('user:id,full_name,role')->latest()->get();

        return response()->json([
            'success' => true,
            'data' => $accessLogs,
        ]);
    }

    public function latestSuccessful(Request $request)
    {
        $latestLog = $request->user()->accessLogs()
            ->where('access_status', 'success')
            ->with('gate:id,gate_name', 'user:id,full_name,role')
            ->latest()
            ->first();

        return response()->json([
            'success' => true,
            'data' => $latestLog,
        ]);
    }
}

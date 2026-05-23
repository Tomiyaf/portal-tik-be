<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class AccessLogController extends Controller
{
    public function index(Request $request)
    {
        $accessLogs = $request->user()->accessLogs()->with('gate')->latest()->get();

        return response()->json([
            'success' => true,
            'data' => $accessLogs,
        ]);
    }
}

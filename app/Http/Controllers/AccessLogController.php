<?php

namespace App\Http\Controllers;

use App\Models\AccessLog;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AccessLogController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
        'access_status' => ['sometimes', Rule::in(['success', 'pending', 'failed'])],
        'access_method' => ['sometimes', Rule::in(['mobile', 'web'])],
        'action' => ['sometimes', Rule::in(['open', 'close', 'entry', 'exit'])],
        'sort_order' => ['sometimes', Rule::in(['asc', 'desc'])],
        'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ]);

        $query = AccessLog::query();

        // Filter by access_status
        if ($request->filled('access_status')) {
            $query->where('access_status', $request->access_status);
        }

        // Filter by access_method
        if ($request->filled('access_method')) {
            $query->where('access_method', $request->access_method);
        }

        // Filter by action
        if ($request->filled('action')) {
            $query->where('action', $request->action);
        }
        
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy('id', $sortOrder);
        $perPage = $request->integer('per_page', 15);
        $accessLogs = $query->with('user:id,full_name,role')->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $accessLogs->items(),
            'pagination' => [
                'total' => $accessLogs->total(),
                'per_page' => $accessLogs->perPage(),
                'current_page' => $accessLogs->currentPage(),
                'last_page' => $accessLogs->lastPage(),
                'from' => $accessLogs->firstItem(),
                'to' => $accessLogs->lastItem(),
            ],
        ]);
    }

    public function lastOpened(Request $request)
    {
        $latestLog = $request->user()->accessLogs()
            ->where('access_status', 'success')
            ->whereIn('action', ['open','entry'])
            ->with('gate:id,gate_name', 'user:id,full_name,role')
            ->latest()
            ->first();

        return response()->json([
            'success' => true,
            'data' => $latestLog,
        ]);
    }
}

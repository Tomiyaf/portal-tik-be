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
            'search' => ['sometimes', 'string', 'max:255'],
            'period' => ['sometimes', Rule::in(['24h', '7d', '30d'])],
            'access_status' => ['sometimes', Rule::in(['success', 'pending', 'failed'])],
            'access_method' => ['sometimes', Rule::in(['mobile', 'web', 'desktop'])],
            'action' => ['sometimes', Rule::in(['open', 'close', 'entry', 'exit'])],
            'sort_order' => ['sometimes', Rule::in(['asc', 'desc'])],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:200'],
        ]);

        $query = AccessLog::with(['user:id,full_name,role', 'gate:id,gate_name']);

        if ($request->filled('period')) {
            $from = match ($request->period) {
                '24h' => now()->subHours(24),
                '7d' => now()->subDays(7),
                '30d' => now()->subDays(30),
            };

            $query->where('created_at', '>=', $from);
        }
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

        if ($request->filled('search')) {
            $search = addcslashes($request->search, '%_');
            $query->where(function ($q) use ($search) {
                $q->where('notes', 'like', "%{$search}%")
                    ->orWhere('action', 'like', "%{$search}%")
                    ->orWhere('access_method', 'like', "%{$search}%")
                    ->orWhere('access_status', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($userQuery) use ($search) {
                        $userQuery->where('full_name', 'like', "%{$search}%");
                    });
            });
        }
        
        $sortOrder = $request->get('sort_order', 'desc');
        $perPage = $request->integer('per_page', 15);
        $accessLogs = $query
            ->orderBy('id', $sortOrder)
            ->paginate($perPage);

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
        $latestOpened = AccessLog::query()
            ->where('access_status', 'success')
            ->whereIn('action', ['open', 'entry', 'exit'])
            ->with('gate:id,gate_name', 'user:id,full_name,role')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->first();

        return response()->json([
            'success' => true,
            'data' => $latestOpened,
        ]);
    }
}

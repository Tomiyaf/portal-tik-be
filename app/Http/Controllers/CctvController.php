<?php

namespace App\Http\Controllers;

use App\Models\Cctv;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\Rule;

class CctvController extends Controller
{
        public function getMain(): JsonResponse
    {
        try {
            $cctv = Cctv::first();

            return response()->json([
                'success' => true,
                'data' => $cctv,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'data' => null,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
    
    public function index(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => Cctv::all(),
        ]);
    }

    public function show(Cctv $cctv): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $cctv,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'camera_name' => ['required', 'string', 'max:255'],
            'path' => ['required', 'string', 'max:255', 'unique:cctvs,path'],
            'stream_url' => ['required', 'string'],
        ]);

        Http::post(
            env('MEDIAMTX_API_URL') . "/v3/config/paths/add/{$validated['path']}",
            [
                'source' => $validated['stream_url'],
                'sourceOnDemand' => true,
            ]
        )->throw();

        $cctv = Cctv::create([
            'camera_name' => $validated['camera_name'],
            'path' => $validated['path'],
            'stream_url' => $validated['stream_url'],
            'is_active' => true,
        ]);

        return response()->json([
            'success' => true,
            'data' => $cctv,
        ], 201);
    }

    public function update(Request $request, Cctv $cctv): JsonResponse
    {
        $validated = $request->validate([
            'camera_name' => ['sometimes', 'required', 'string', 'max:255'],

            'path' => [
                'sometimes',
                'required',
                'string',
                'max:255',
                Rule::unique('cctvs')->ignore($cctv->id),
            ],

            'stream_url' => ['sometimes', 'required', 'string'],

            'is_active' => ['sometimes', 'required', 'boolean'],
        ]);

        $newPath = $validated['path'] ?? $cctv->path;
        $newStreamUrl = $validated['stream_url'] ?? $cctv->stream_url;

        $pathChanged = $newPath !== $cctv->path;
        $streamChanged = $newStreamUrl !== $cctv->stream_url;

        if ($pathChanged || $streamChanged) {

            Http::delete(
                rtrim(env('MEDIAMTX_API_URL'), '/')
                . "/v3/config/paths/delete/{$cctv->path}"
            )->throw();

            Http::post(
                rtrim(env('MEDIAMTX_API_URL'), '/')
                . "/v3/config/paths/add/{$newPath}",
                [
                    'source' => $newStreamUrl,
                    'sourceOnDemand' => true,
                ]
            )->throw();
        }

        $cctv->update($validated);

        return response()->json([
            'success' => true,
            'data' => $cctv->fresh(),
        ]);
    }

    public function destroy(Cctv $cctv): JsonResponse
    {
        Http::delete(
            rtrim(env('MEDIAMTX_API_URL'), '/')
            . "/v3/config/paths/delete/{$cctv->path}"
        )->throw();

        $cctv->delete();

        return response()->json([
            'success' => true,
            'data' => null,
        ]);
    }
}
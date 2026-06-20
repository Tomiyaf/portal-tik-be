<?php

namespace App\Http\Controllers;

use App\Models\Cctv;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;

class CCTVController extends Controller
{
    private function syncGo2RtcConfig(): void
    {
        $cctvs = Cctv::all();

        $yaml = <<<YAML
    api:
      listen: ":1984"

    rtsp:
      listen: ":8554"

    webrtc:
      listen: ":8555"
      candidates:
        - gate.tik.unila.ac.id:8555

    streams:

    YAML;

        foreach ($cctvs as $cctv) {
            $yaml .= "  {$cctv->path}:\n";
            $yaml .= "    - {$cctv->stream_url}\n";

            if ($cctv->type === 'intercom') {
                $yaml .= "    - " . $this->buildIsapiUrl($cctv->stream_url) . "\n";
            }
        }

        $configPath = storage_path('app/private/go2rtc/go2rtc.yml');

        File::ensureDirectoryExists(dirname($configPath));

        File::put($configPath, $yaml);

        $result = Process::run([
            'C:\Program Files\nssm\win64\nssm.exe',
            'restart',
            'GateTikGo2RTC',
        ]);

        if (!$result->successful()) {
            throw new \Exception("Failed to restart Go2RTC: " . $result->errorOutput());
        }
    }

    private function buildIsapiUrl(string $rtspUrl): string
    {
        $parts = parse_url($rtspUrl);

        $user = $parts['user'] ?? '';
        $pass = $parts['pass'] ?? '';
        $host = $parts['host'] ?? '';

        return "isapi://{$user}:{$pass}@{$host}:80/";
    }

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
            'type' => ['required', Rule::in(['monitor', 'intercom'])],
        ]);

        $cctv = Cctv::create($validated);

        $this->syncGo2RtcConfig();

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
                Rule::unique('cctvs', 'path')->ignore($cctv->id),
            ],

            'stream_url' => ['sometimes', 'required', 'string'],

            'type' => [
                'sometimes',
                'required',
                Rule::in(['monitor', 'intercom']),
            ],
        ]);

        $cctv->update($validated);

        $this->syncGo2RtcConfig();

        return response()->json([
            'success' => true,
            'data' => $cctv->fresh(),
        ]);
    }

    public function destroy(Cctv $cctv): JsonResponse
    {
        $cctv->delete();

        $this->syncGo2RtcConfig();

        return response()->json([
            'success' => true,
            'data' => null,
        ]);
    }
}
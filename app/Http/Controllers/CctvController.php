<?php

namespace App\Http\Controllers;

use App\Models\Cctv;
use Illuminate\Http\JsonResponse;

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
}

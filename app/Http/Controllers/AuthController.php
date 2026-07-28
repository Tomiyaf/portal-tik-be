<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'password' => ['required', 'string'],
        ]);

        $user = User::where('email', $credentials['email'])->first();

        if (!$user || !Hash::check($credentials['password'], $user->password)) {
            return response()->json([
                'success' => false,
                'data'    => null,
                'message' => 'Username/password tidak valid.',
            ], 401);
        }

        if ($user->status !== 'active') {
            return response()->json([
                'success' => false,
                'data'    => null,
                'message' => 'Akun Anda belum aktif. Mohon tunggu konfirmasi dari admin.',
            ], 403);
        }

        $user->forceFill([
            'last_login_at' => now(),
        ])->save();

        $token = $user->createToken('api')->plainTextToken;

        return response()->json([
            'success' => true,
            'data' => [
                'token' => $token,
                'token_type' => 'Bearer',
                'user' => $user,
            ],
        ]);
    }

    public function register (Request $request): JsonResponse
    {
        $validated = $request->validate([
            'full_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'npm_nip' => ['required', 'string', 'max:50', 'regex:/^[a-zA-Z0-9_\-]+$/', 'unique:users,npm_nip'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'ktm' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        $ktmPath = $request->file('ktm')->store('ktm');

        $user = User::create([
            'full_name' => $validated['full_name'],
            'email' => $validated['email'],
            'npm_nip' => $validated['npm_nip'],
            'password' => Hash::make($validated['password']),
            'role' => 'mahasiswa',
            'status' => 'pending',
            'ktm_path' => $ktmPath,
        ]);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $user->id,
                'full_name' => $user->full_name,
                'email' => $user->email,
                'npm_nip' => $user->npm_nip,
                'role' => $user->role,
                'status' => $user->status,
            ],
        ], 201);
    }

    public function logout(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user) {
            $user->currentAccessToken()?->delete();
        }

        return response()->json([
            'success' => true,
            'data' => null,
            'message' => 'Logged out.',
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $request->user(),
        ]);
    }
}

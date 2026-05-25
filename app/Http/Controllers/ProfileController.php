<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;


class ProfileController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'success' => true,
            'data' => $user,
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'full_name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'email', 'max:255', 'unique:users,email,' . $user->id],
            'password' => ['sometimes', 'nullable', 'string', 'min:8'],
            'phone_number' => ['sometimes', 'string', 'max:20'],

        ]);

        // Jika password diisi (bukan null/kosong)
        if (!empty($data['password'])) {
            $data['password'] = bcrypt($data['password']);
        } else {
            // Hapus password dari array jika nilainya kosong/null agar tidak ikut ter-update
            unset($data['password']);
        }

        $user->update($data);
        $user->refresh();

        return response()->json([
            'success' => true,
            'data' => $user,
        ]);
    }
}

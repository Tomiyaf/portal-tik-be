<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\User;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class UserController extends Controller
{
    // List users with filter, search, pagination
    public function index(Request $request)
    {
        $request->validate([
        'role' => ['sometimes', Rule::in(['admin', 'staff', 'mahasiswa'])],
        'status' => ['sometimes', Rule::in(['pending', 'active', 'suspended'])],
        'search' => ['sometimes', 'string', 'max:255'],
        'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ]);

        $query = User::query()->where('id', '!=', 1);

        // Filter by role
        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Search (by full_name, email, npm_nip, phone_number)
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('full_name', 'like', "%$search%")
                  ->orWhere('email', 'like', "%$search%")
                  ->orWhere('npm_nip', 'like', "%$search%")
                  ->orWhere('phone_number', 'like', "%$search%") ;
            });
        }

        // Pagination
        $perPage = $request->integer('per_page', 15);
        $users = $query->orderBy('id')->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $users->items(),
            'pagination' => [
                'total' => $users->total(),
                'per_page' => $users->perPage(),
                'current_page' => $users->currentPage(),
                'last_page' => $users->lastPage(),
                'from' => $users->firstItem(),
                'to' => $users->lastItem(),
            ],
        ]);
    }

    // Show user detail
    public function show($id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json([
                'success' => false,
                'data' => null,
                'message' => 'User tidak ditemukan.',
            ], 404);
        }
        
        return response()->json([
            'success' => true,
            'data' => [
                'id' => $user->id,
                'full_name' => $user->full_name,
                'email' => $user->email,
                'npm_nip' => $user->npm_nip,
                'phone_number' => $user->phone_number,
                'role' => $user->role,
                'status' => $user->status,
                'profile_photo' => null,
                'ktm_path' => $user->ktm_path ? "/api/users/{$user->id}/ktm" : null,
                'last_login_at' => $user->last_login_at,
            ],
        ]);
    }

    // Create user
    public function store(Request $request)
    {
        $validated = $request->validate([
            'full_name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6',
            'npm_nip' => 'required|string|unique:users,npm_nip',
            'phone_number' => 'nullable|string|max:20',
            'role' => ['required', Rule::in(['admin', 'staff', 'mahasiswa'])],
            'status' => ['required', Rule::in(['pending', 'active', 'suspended'])],
            'profile_photo' => 'nullable|string',
            'ktm_path' => 'nullable|string',
            'last_login_at' => 'nullable|date',
        ]);
        $validated['password'] = Hash::make($validated['password']);
        $user = User::create($validated);
        return response()->json([
            'success' => true,
            'data' => $user,
        ]);
    }

    // Update user (patch)
    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);
        $validated = $request->validate([
            'full_name' => 'sometimes|required|string|max:255',
            'email' => [
                'sometimes', 'required', 'email', Rule::unique('users')->ignore($user->id)
            ],
            'password' => 'nullable|string|min:8',
            'npm_nip' => [
                'sometimes', 'required', Rule::unique('users')->ignore($user->id)
            ],
            'phone_number' => 'sometimes|nullable|string|max:20',
            'role' => ['sometimes', 'required', Rule::in(['admin', 'staff', 'mahasiswa'])],
            'status' => ['sometimes', 'required', Rule::in(['pending', 'active', 'suspended'])],
            // 'profile_photo' => 'nullable|string',
            'ktm_path' => 'sometimes|nullable|string',
            // 'last_login_at' => 'nullable|date',
        ]);

        if (!empty($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        $user->update($validated);
        return response()->json([
            'success' => true,
            'data' => $user,
        ]);
    }

    // Delete user
    public function destroy($id)
    {
        $user = User::find($id);
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User tidak ditemukan.',
            ], 404);
        }

        if ($user->id === 1) {
            return response()->json([
                'message' => 'Super admin tidak dapat dihapus.',
            ], 403);
        }

        $user->delete();
        return response()->json([
            'success' => true,
            'message' => 'User berhasil dihapus.',
        ]);
    }

    public function previewKtm(User $user)
    {   
        if (!$user->ktm_path) {
            return response()->noContent();
        }

        return response()->file(Storage::path($user->ktm_path), [
            'Content-Type' => Storage::mimeType($user->ktm_path),
            'Content-Disposition' => 'inline',
        ]);
    }
}

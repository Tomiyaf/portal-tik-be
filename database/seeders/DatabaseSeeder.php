<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Gate;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::create([
            'full_name' => 'Super Admin',
            'email' => 'admin@example.com',
            'password' => Hash::make('admin123'),
            'npm_nip' => 'ADM001',
            'phone_number' => '081234567890',
            'role' => 'admin',
            'status' => 'active',
            'profile_photo' => null,
            'last_login_at' => null,
        ]);

        Gate::create([
            'gate_name' => 'Gate 1',
            'latitude' => -6.200000,
            'longitude' => 106.816666,
            'allowed_radius_meter' => 100,
            'current_status' => 'closed',
            'is_active' => true,
        ]);
    }
}

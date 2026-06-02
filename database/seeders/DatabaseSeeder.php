<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Gate;
use App\Models\Cctv;
use App\Models\IotDevice;
use App\Models\ParkingQuota;
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
        User::insert([
            [
                'full_name' => 'Super Admin',
                'email' => 'admin@example.com',
                'password' => Hash::make('admin123'),
                'npm_nip' => 'ADM001',
                'phone_number' => '081234567890',
                'role' => 'admin',
                'status' => 'active',
                'profile_photo' => null,
                'last_login_at' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'full_name' => 'John Doe',
                'email' => 'student@example.com',
                'password' => Hash::make('student123'),
                'npm_nip' => 'STU001',
                'phone_number' => '081234567891',
                'role' => 'mahasiswa',
                'status' => 'active',
                'profile_photo' => null,
                'last_login_at' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'full_name' => 'Jane Smith',
                'email' => 'student2@example.com',
                'password' => Hash::make('student123'),
                'npm_nip' => 'STU002',
                'phone_number' => '081234567892',
                'role' => 'mahasiswa',
                'status' => 'pending',
                'profile_photo' => null,
                'last_login_at' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        ]);

        ParkingQuota::create([
            'total_slots' => 50,
            'used_slots' => 0,
            // 'status' => 'available',
            'auto_restrict_student' => true,
            // 'updated_by' => 1,
        ]);

        Gate::create([
            'gate_name' => 'Gate 1',
            'latitude' => -5.362724788161964,
            'longitude' => 105.24102403915745,
            'allowed_radius_meter' => 100,
            // 'current_status' => 'closed',
            // 'is_active' => true,
        ]);

        Cctv::create([
            'camera_name' => 'Main Gate Camera',
            'stream_url' => 'http://localhost:8554/gate1',
            'is_active' => true,
        ]);

        IotDevice::create([
            'device_name' => 'ESP8266 Gate 1',
            'device_uid' => 'ESP8266-GATE1-001',
            'gate_id' => 1,
            'firmware_version' => 'v1.0.0',
            'status' => 'offline',
            'last_online_at' => now()
        ]);
    }
}

<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Seed Administrator User
        User::updateOrCreate(
            ['email' => 'admin@local.com'],
            [
                'name' => 'System Administrator',
                'password' => Hash::make('admin123'),
                'role' => 'admin',
                'status' => 'active',
            ]
        );

        // 2. Seed Default Sample Biometric Machine
        DB::table('attendance_machines')->updateOrInsert(
            ['ip_address' => '192.168.1.201', 'port' => 4370],
            [
                'name' => 'Main Office Gate',
                'protocol' => 'auto',
                'comm_key' => 0,
                'location' => 'Front Entrance',
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        // 3. Seed Default System Settings
        $settings = [
            ['key' => 'app_title', 'value' => 'ZK Attendance & HRMS ERP', 'description' => 'System Title'],
            ['key' => 'auto_fetch_enabled', 'value' => '0', 'description' => 'Enable background automated polling'],
            ['key' => 'auto_fetch_interval', 'value' => '15', 'description' => 'Polling interval in minutes'],
            ['key' => 'clear_device_logs', 'value' => '0', 'description' => 'Clear machine memory after successful fetch'],
            ['key' => 'grace_period_minutes', 'value' => '15', 'description' => 'Late mark grace period in minutes'],
        ];

        foreach ($settings as $setting) {
            DB::table('system_settings')->updateOrInsert(['key' => $setting['key']], $setting);
        }

        // 4. Initial Audit Log
        DB::table('audit_logs')->insert([
            'username' => 'System',
            'action' => 'SYSTEM_INITIALIZED',
            'details' => 'Application database seeded with default administrator, settings and sample machine.',
            'ip_address' => '127.0.0.1',
            'status' => 'success',
            'created_at' => now(),
        ]);
    }
}

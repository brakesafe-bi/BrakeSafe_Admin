<?php

namespace Database\Seeders;

use App\Models\Admin;
use App\Models\BrakeEvent;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Create 2 admin users
        Admin::create([
            'name' => 'Admin One',
            'full_name' => 'Admin One',
            'email' => 'admin1@brakesafe.com',
            'password' => Hash::make('password'),
            'is_verified' => true,
        ]);

        Admin::create([
            'name' => 'Admin Two',
            'full_name' => 'Admin Two',
            'email' => 'admin2@brakesafe.com',
            'password' => Hash::make('password'),
            'is_verified' => true,
        ]);

        $municipalities = ['Bantayan', 'Madridejos', 'Santa Fe'];

        $classifications = ['soft', 'medium', 'hard', 'emergency'];
        for ($i = 0; $i < 60; $i++) {
            $classification = $classifications[array_rand($classifications)];

            BrakeEvent::create([
                'vehicle_id' => 'VH' . str_pad((string) rand(1, 5), 4, '0', STR_PAD_LEFT),
                'latitude' => 10.2 + (rand(-100, 100) / 1000),
                'longitude' => 123.8 + (rand(-100, 100) / 1000),
                'speed_kmh' => rand(10, 80),
                'deceleration_force' => rand(20, 150),
                'brake_classification' => $classification,
                'municipality' => $municipalities[array_rand($municipalities)],
                'barangay' => 'Barangay ' . chr(65 + rand(0, 5)),
                'direction' => ['N', 'NE', 'E', 'SE', 'S', 'SW', 'W', 'NW'][array_rand(['N', 'NE', 'E', 'SE', 'S', 'SW', 'W', 'NW'])],
                'timestamp' => Carbon::now()->subHours(rand(0, 48)),
            ]);
        }
    }
}

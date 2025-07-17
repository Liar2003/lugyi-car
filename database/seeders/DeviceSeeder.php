<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Device;
use App\Models\User;

class DeviceSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::all();
        $userCount = $users->count();
        $devices = collect();
        for ($i = 0; $i < 20; $i++) {
            $devices->push(Device::factory()->create([
                // Randomly assign to a user (or leave null)
                'user_identifier' => $userCount > 0 && rand(0, 1) ? $users->random()->email : null,
            ]));
        }
    }
}

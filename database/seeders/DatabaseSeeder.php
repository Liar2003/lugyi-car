<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Seed 2 users
        \App\Models\User::factory(2)->create();

        // Seed 20 devices
        $this->call(DeviceSeeder::class);

        // Seed 150 contents
        \App\Models\Content::factory(150)->create();
    }
}

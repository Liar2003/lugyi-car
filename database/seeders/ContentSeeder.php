<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Content;

class ContentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create 100 standard (non-VIP) content items
        Content::factory()->count(20)->standard()->create();

        // Create 30 VIP content items
        Content::factory()->count(10)->vip()->create();

        // Optionally, create a few more with mixed VIP status
        Content::factory()->count(5)->create(); // Uses the default 30% VIP chance
    }
}

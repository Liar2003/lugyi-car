<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Device;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Device>
 */
class DeviceFactory extends Factory
{
    protected $model = Device::class;

    public function definition(): array
    {
        return [
            'device_id' => 'dev-' . $this->faker->unique()->uuid(),
            'api_token' => bin2hex(random_bytes(32)),
            'user_identifier' => null, // Set in seeder if needed
            'device_token' => $this->faker->optional()->sha256,
            'platform' => $this->faker->randomElement(['android', 'ios', 'web', 'desktop']),
            'osversion' => $this->faker->optional()->numerify('##.##'),
            'appversion' => $this->faker->optional()->numerify('##.##'),
            'subscription_key' => null,
            'is_vip' => $this->faker->boolean(20),
            'last_active_at' => $this->faker->dateTimeBetween('-1 month', 'now'),
            'vip_expires_at' => $this->faker->optional(0.2)->dateTimeBetween('now', '+1 year'),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}

<?php

namespace Database\Factories;

use App\Models\GtfsStop;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\GtfsStop>
 */
class GtfsStopFactory extends Factory
{
    protected $model = GtfsStop::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'stop_id' => $this->faker->unique()->regexify('[A-Z0-9]{8}'),
            'stop_name' => $this->faker->city.' Station',
            'stop_lat' => $this->faker->latitude,
            'stop_lon' => $this->faker->longitude,
            'platform_code' => $this->faker->randomElement(['1', '2', '3', '1A', '2B', '3C', '']),
        ];
    }
}

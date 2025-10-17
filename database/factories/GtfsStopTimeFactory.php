<?php

namespace Database\Factories;

use App\Models\GtfsStopTime;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\GtfsStopTime>
 */
class GtfsStopTimeFactory extends Factory
{
    protected $model = GtfsStopTime::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'trip_id' => $this->faker->regexify('[A-Z0-9]{8}'),
            'stop_id' => $this->faker->regexify('[A-Z0-9]{8}'),
            'stop_sequence' => $this->faker->numberBetween(1, 10),
            'arrival_time' => $this->faker->time('H:i:s'),
            'departure_time' => $this->faker->time('H:i:s'),
        ];
    }
}

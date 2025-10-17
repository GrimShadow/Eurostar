<?php

namespace Database\Factories;

use App\Models\GtfsTrip;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\GtfsTrip>
 */
class GtfsTripFactory extends Factory
{
    protected $model = GtfsTrip::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'trip_id' => $this->faker->unique()->regexify('[A-Z0-9]{8}'),
            'route_id' => $this->faker->regexify('[A-Z0-9]{6}'),
            'service_id' => $this->faker->regexify('[A-Z0-9]{6}'),
            'trip_headsign' => $this->faker->city.' Station',
            'trip_short_name' => $this->faker->randomNumber(4),
            'direction_id' => $this->faker->randomElement([0, 1]),
            'shape_id' => $this->faker->regexify('[A-Z0-9]{6}'),
            'wheelchair_accessible' => $this->faker->randomElement([0, 1, 2]),
        ];
    }
}

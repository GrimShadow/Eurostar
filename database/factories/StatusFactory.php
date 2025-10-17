<?php

namespace Database\Factories;

use App\Models\Status;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Status>
 */
class StatusFactory extends Factory
{
    protected $model = Status::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'status' => $this->faker->randomElement(['On Time', 'Delayed', 'Cancelled', 'Boarding']),
            'color_rgb' => $this->faker->randomElement(['0,255,0', '255,165,0', '255,0,0', '0,0,255']),
        ];
    }
}

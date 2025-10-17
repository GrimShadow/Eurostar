<?php

namespace Database\Factories;

use App\Models\TrainRule;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\TrainRule>
 */
class TrainRuleFactory extends Factory
{
    protected $model = TrainRule::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'action' => $this->faker->randomElement(['set_status', 'make_announcement']),
            'action_value' => $this->faker->randomNumber(),
            'is_active' => true,
            'priority' => $this->faker->numberBetween(0, 100),
            'execution_mode' => $this->faker->randomElement(['first_match', 'all_matches', 'highest_priority']),
        ];
    }
}

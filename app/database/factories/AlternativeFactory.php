<?php

namespace Database\Factories;

use App\Models\Alternative;
use App\Models\Question;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Alternative>
 */
class AlternativeFactory extends Factory
{
  /**
   * Define the model's default state.
   *
   * @return array<string, mixed>
   */
  public function definition(): array
  {
    return [
      'question_id' => Question::factory(),
      'text' => fake()->sentence(),
      'is_correct' => false,
    ];
  }

  public function correct(): static
  {
    return $this->state(fn (array $attributes) => [
      'is_correct' => true,
    ]);
  }
}

<?php

namespace Database\Factories;

use App\Models\Exam;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Exam>
 */
class ExamFactory extends Factory
{
  /**
   * Define the model's default state.
   *
   * @return array<string, mixed>
   */
  public function definition(): array
  {
    return [
      'title' => fake()->sentence(4),
      'description' => fake()->paragraph(),
      'is_available' => true,
    ];
  }

  public function unavailable(): static
  {
    return $this->state(fn(array $attributes) => [
      'is_available' => false,
    ]);
  }
}

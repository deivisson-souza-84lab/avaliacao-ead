<?php

namespace Database\Factories;

use App\Models\Alternative;
use App\Models\ExamAttempt;
use App\Models\ExamAttemptAnswer;
use App\Models\Question;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ExamAttemptAnswer>
 */
class ExamAttemptAnswerFactory extends Factory
{
  /**
   * Define the model's default state.
   *
   * @return array<string, mixed>
   */
  public function definition(): array
  {
    return [
      'exam_attemp_id' => ExamAttempt::factory(),
      'question_id' => Question::factory(),
      'alternative_id' => Alternative::factory(),
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

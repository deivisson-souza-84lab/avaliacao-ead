<?php

namespace Database\Factories;

use App\Models\Exam;
use App\Models\ExamAttempt;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ExamAttempt>
 */
class ExamAttemptFactory extends Factory
{
  /**
   * Define the model's default state.
   *
   * @return array<string, mixed>
   */
  public function definition(): array
  {
    $totalQuestions = fake()->numberBetween(3, 10);
    $score = fake()->numberBetween(0, $totalQuestions);

    return [
      'exam_id' => Exam::factory(),
      'student_identifier' => fake()->unique()->safeEmail(),
      'student_name' => fake()->name(),
      'score' => $score,
      'total_questions' => $totalQuestions,
      'percentage' => round(($score / $totalQuestions) * 100, 2),
      'submitted_at' => now(),
    ];
  }
}

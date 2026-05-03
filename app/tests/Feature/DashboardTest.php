<?php

namespace Tests\Feature;

use App\Models\Exam;
use App\Models\ExamAttempt;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class DashboardTest extends TestCase
{
  use RefreshDatabase;

  protected function setUp(): void
  {
    parent::setUp();

    Cache::flush();
  }

  public function test_dashboard_summary_returns_average_best_score_and_total_attempts(): void
  {
    $exam = Exam::factory()->create();

    ExamAttempt::factory()
      ->for($exam)
      ->create([
        'student_identifier' => 'aluno1@email.com',
        'student_name' => 'Aluno 1',
        'score' => 3,
        'total_questions' => 3,
        'percentage' => 100,
      ]);

    ExamAttempt::factory()
      ->for($exam)
      ->create([
        'student_identifier' => 'aluno2@email.com',
        'student_name' => 'Aluno 2',
        'score' => 1,
        'total_questions' => 3,
        'percentage' => 33.33,
      ]);

    $response = $this->getJson('/api/dashboard');

    $response
      ->assertOk()
      ->assertJsonPath('data.average_score', 66.67)
      ->assertJsonPath('data.best_score', 100)
      ->assertJsonPath('data.total_attempts', 2);
  }

  public function test_dashboard_summary_returns_zero_when_there_are_no_attempts(): void
  {
    $response = $this->getJson('/api/dashboard');

    $response
      ->assertOk()
      ->assertJsonPath('data.average_score', 0)
      ->assertJsonPath('data.best_score', 0)
      ->assertJsonPath('data.total_attempts', 0);
  }

  public function test_dashboard_ranking_returns_attempts_ordered_by_best_percentage(): void
  {
    $exam = Exam::factory()->create();

    ExamAttempt::factory()
      ->for($exam)
      ->create([
        'student_identifier' => 'aluno-baixa@email.com',
        'student_name' => 'Aluno Baixa',
        'score' => 1,
        'total_questions' => 3,
        'percentage' => 33.33,
        'submitted_at' => now()->addMinute(),
      ]);

    ExamAttempt::factory()
      ->for($exam)
      ->create([
        'student_identifier' => 'aluno-alta@email.com',
        'student_name' => 'Aluno Alta',
        'score' => 3,
        'total_questions' => 3,
        'percentage' => 100,
        'submitted_at' => now(),
      ]);

    $response = $this->getJson('/api/dashboard/ranking');

    $response
      ->assertOk()
      ->assertJsonPath('data.0.student_identifier', 'aluno-alta@email.com')
      ->assertJsonPath('data.0.percentage', 100)
      ->assertJsonPath('data.1.student_identifier', 'aluno-baixa@email.com')
      ->assertJsonPath('data.1.percentage', 33.33);
  }

  public function test_dashboard_ranking_is_paginated(): void
  {
    $exam = Exam::factory()->create();

    ExamAttempt::factory()
      ->count(15)
      ->for($exam)
      ->create();

    $response = $this->getJson('/api/dashboard/ranking?per_page=10');

    $response
      ->assertOk()
      ->assertJsonCount(10, 'data')
      ->assertJsonPath('meta.per_page', 10)
      ->assertJsonPath('meta.total', 15);
  }

  public function test_dashboard_summary_uses_cache(): void
  {
    Cache::shouldReceive('remember')
      ->once()
      ->withArgs(function ($key, $ttl, $callback) {
        return $key === 'dashboard:summary'
          && is_callable($callback);
      })
      ->andReturn([
        'average_score' => 80,
        'best_score' => 100,
        'total_attempts' => 5,
      ]);

    $response = $this->getJson('/api/dashboard');

    $response
      ->assertOk()
      ->assertJsonPath('data.average_score', 80)
      ->assertJsonPath('data.best_score', 100)
      ->assertJsonPath('data.total_attempts', 5);
  }
}

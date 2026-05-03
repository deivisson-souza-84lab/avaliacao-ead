<?php

namespace Tests\Feature;

use App\Models\Alternative;
use App\Models\Exam;
use App\Models\Question;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class StudentExamTest extends TestCase
{
  use RefreshDatabase;

  public function test_student_can_list_only_available_exams(): void
  {
    Exam::factory()->create([
      'title' => 'Prova disponível',
      'is_available' => true,
    ]);

    Exam::factory()->create([
      'title' => 'Prova indisponível',
      'is_available' => false,
    ]);

    $response = $this->getJson('/api/student/exams');

    $response
      ->assertOk()
      ->assertJsonCount(1, 'data')
      ->assertJsonPath('data.0.title', 'Prova disponível');

    $response->assertJsonMissing([
      'title' => 'Prova indisponível',
    ]);
  }

  public function test_student_can_view_available_exam_without_correct_answers(): void
  {
    $exam = Exam::factory()->create([
      'title' => 'Prova para aluno',
      'is_available' => true,
    ]);

    $question = Question::factory()
      ->for($exam)
      ->create([
        'statement' => 'Qual comando instala dependências PHP?',
      ]);

    Alternative::factory()
      ->for($question)
      ->correct()
      ->create([
        'text' => 'composer install',
      ]);

    Alternative::factory()
      ->for($question)
      ->create([
        'text' => 'npm install',
      ]);

    $response = $this->getJson("/api/student/exams/{$exam->id}");

    $response
      ->assertOk()
      ->assertJsonPath('data.title', 'Prova para aluno')
      ->assertJsonPath('data.questions.0.statement', 'Qual comando instala dependências PHP?')
      ->assertJsonPath('data.questions.0.alternatives.0.text', 'composer install')
      ->assertJsonMissingPath('data.questions.0.alternatives.0.is_correct')
      ->assertJsonMissingPath('data.questions.0.alternatives.1.is_correct');
  }

  public function test_student_cannot_view_unavailable_exam(): void
  {
    $exam = Exam::factory()->create([
      'is_available' => false,
    ]);

    $response = $this->getJson("/api/student/exams/{$exam->id}");

    $response->assertNotFound();
  }
}

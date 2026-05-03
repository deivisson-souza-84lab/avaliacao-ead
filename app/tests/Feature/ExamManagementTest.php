<?php

namespace Tests\Feature;

use App\Models\Alternative;
use App\Models\Exam;
use App\Models\Question;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class ExamManagementTest extends TestCase
{
  use RefreshDatabase;

  public function test_teacher_can_create_exam_with_questions_and_alternatives(): void
  {
    $payload = [
      'title' => 'Prova de PHP',
      'description' => 'Prova sobre fundamentos de PHP.',
      'is_available' => true,
      'questions' => [
        [
          'statement' => 'Qual comando instala dependências PHP?',
          'alternatives' => [
            [
              'text' => 'composer install',
              'is_correct' => true,
            ],
            [
              'text' => 'npm install',
              'is_correct' => false,
            ],
          ],
        ],
      ],
    ];

    $response = $this->postJson('/api/exams', $payload);

    $response
      ->assertCreated()
      ->assertJsonPath('data.title', 'Prova de PHP')
      ->assertJsonPath('data.questions.0.statement', 'Qual comando instala dependências PHP?')
      ->assertJsonPath('data.questions.0.alternatives.0.text', 'composer install')
      ->assertJsonPath('data.questions.0.alternatives.0.is_correct', true);

    $this->assertDatabaseHas('exams', [
      'title' => 'Prova de PHP',
    ]);

    $this->assertDatabaseHas('questions', [
      'statement' => 'Qual comando instala dependências PHP?',
    ]);

    $this->assertDatabaseHas('alternatives', [
      'text' => 'composer install',
      'is_correct' => true,
    ]);
  }

  public function test_teacher_cannot_create_question_without_correct_alternative(): void
  {
    $payload = [
      'title' => 'Prova inválida',
      'questions' => [
        [
          'statement' => 'Pergunta sem resposta correta?',
          'alternatives' => [
            [
              'text' => 'Alternativa A',
              'is_correct' => false,
            ],
            [
              'text' => 'Alternativa B',
              'is_correct' => false,
            ],
          ],
        ],
      ],
    ];

    $response = $this->postJson('/api/exams', $payload);

    $response
      ->assertUnprocessable()
      ->assertJsonValidationErrors('questions.0.alternatives');

    $this->assertDatabaseCount('exams', 0);
    $this->assertDatabaseCount('questions', 0);
    $this->assertDatabaseCount('alternatives', 0);
  }

  public function test_teacher_cannot_create_question_with_more_than_one_correct_alternative(): void
  {
    $payload = [
      'title' => 'Prova inválida',
      'questions' => [
        [
          'statement' => 'Pergunta com duas corretas?',
          'alternatives' => [
            [
              'text' => 'Alternativa A',
              'is_correct' => true,
            ],
            [
              'text' => 'Alternativa B',
              'is_correct' => true,
            ],
          ],
        ],
      ],
    ];

    $response = $this->postJson('/api/exams', $payload);

    $response
      ->assertUnprocessable()
      ->assertJsonValidationErrors('questions.0.alternatives');

    $this->assertDatabaseCount('exams', 0);
    $this->assertDatabaseCount('questions', 0);
    $this->assertDatabaseCount('alternatives', 0);
  }

  public function test_teacher_can_list_exams(): void
  {
    Exam::factory()
      ->count(2)
      ->create();

    $response = $this->getJson('/api/exams');

    $response
      ->assertOk()
      ->assertJsonCount(2, 'data');
  }

  public function test_teacher_can_view_exam_with_questions_and_alternatives(): void
  {
    $exam = Exam::factory()->create();

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

    $response = $this->getJson("/api/exams/{$exam->id}");

    $response
      ->assertOk()
      ->assertJsonPath('data.id', $exam->id)
      ->assertJsonPath('data.questions.0.statement', 'Qual comando instala dependências PHP?')
      ->assertJsonPath('data.questions.0.alternatives.0.text', 'composer install')
      ->assertJsonPath('data.questions.0.alternatives.0.is_correct', true);
  }
}

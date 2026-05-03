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

  public function test_teacher_can_update_exam_with_questions_and_alternatives(): void
  {
    $exam = Exam::factory()->create([
      'title' => 'Prova antiga',
      'description' => 'Descrição antiga',
      'is_available' => true,
    ]);

    $question = Question::factory()
      ->for($exam)
      ->create([
        'statement' => 'Questão antiga',
      ]);

    Alternative::factory()
      ->for($question)
      ->correct()
      ->create([
        'text' => 'Alternativa antiga correta',
      ]);

    Alternative::factory()
      ->for($question)
      ->create([
        'text' => 'Alternativa antiga errada',
      ]);

    $payload = [
      'title' => 'Prova atualizada',
      'description' => 'Descrição atualizada',
      'is_available' => false,
      'questions' => [
        [
          'statement' => 'Questão atualizada',
          'alternatives' => [
            [
              'text' => 'Nova alternativa correta',
              'is_correct' => true,
            ],
            [
              'text' => 'Nova alternativa errada',
              'is_correct' => false,
            ],
          ],
        ],
      ],
    ];

    $response = $this->putJson("/api/exams/{$exam->id}", $payload);

    $response
      ->assertOk()
      ->assertJsonPath('data.title', 'Prova atualizada')
      ->assertJsonPath('data.description', 'Descrição atualizada')
      ->assertJsonPath('data.is_available', false)
      ->assertJsonPath('data.questions.0.statement', 'Questão atualizada')
      ->assertJsonPath('data.questions.0.alternatives.0.text', 'Nova alternativa correta')
      ->assertJsonPath('data.questions.0.alternatives.0.is_correct', true);

    $this->assertDatabaseHas('exams', [
      'id' => $exam->id,
      'title' => 'Prova atualizada',
      'description' => 'Descrição atualizada',
      'is_available' => false,
    ]);

    $this->assertDatabaseMissing('questions', [
      'statement' => 'Questão antiga',
    ]);

    $this->assertDatabaseMissing('alternatives', [
      'text' => 'Alternativa antiga correta',
    ]);

    $this->assertDatabaseHas('questions', [
      'exam_id' => $exam->id,
      'statement' => 'Questão atualizada',
    ]);

    $this->assertDatabaseHas('alternatives', [
      'text' => 'Nova alternativa correta',
      'is_correct' => true,
    ]);
  }

  public function test_teacher_cannot_update_exam_with_invalid_correct_alternatives(): void
  {
    $exam = Exam::factory()->create();

    $payload = [
      'title' => 'Prova inválida',
      'questions' => [
        [
          'statement' => 'Pergunta sem correta?',
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

    $response = $this->putJson("/api/exams/{$exam->id}", $payload);

    $response
      ->assertUnprocessable()
      ->assertJsonValidationErrors('questions.0.alternatives');
  }

  public function test_teacher_can_delete_exam(): void
  {
    $exam = Exam::factory()->create();

    $question = Question::factory()
      ->for($exam)
      ->create();

    Alternative::factory()
      ->for($question)
      ->correct()
      ->create();

    Alternative::factory()
      ->for($question)
      ->create();

    $response = $this->deleteJson("/api/exams/{$exam->id}");

    $response->assertNoContent();

    $this->assertDatabaseMissing('exams', [
      'id' => $exam->id,
    ]);

    $this->assertDatabaseMissing('questions', [
      'id' => $question->id,
    ]);
  }

  public function test_teacher_cannot_create_exam_with_duplicate_title(): void
  {
    Exam::factory()->create([
      'title' => 'Prova de PHP',
    ]);

    $payload = [
      'title' => 'Prova de PHP',
      'description' => 'Outra prova com mesmo título.',
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
      ->assertUnprocessable()
      ->assertJsonValidationErrors('title');

    $this->assertDatabaseCount('exams', 1);
  }

  public function test_teacher_cannot_update_exam_with_duplicate_title(): void
  {
    Exam::factory()->create([
      'title' => 'Prova já existente',
    ]);

    $exam = Exam::factory()->create([
      'title' => 'Prova original',
    ]);

    $payload = [
      'title' => 'Prova já existente',
      'description' => 'Tentando usar título duplicado.',
      'is_available' => true,
      'questions' => [
        [
          'statement' => 'Pergunta válida?',
          'alternatives' => [
            [
              'text' => 'Alternativa correta',
              'is_correct' => true,
            ],
            [
              'text' => 'Alternativa incorreta',
              'is_correct' => false,
            ],
          ],
        ],
      ],
    ];

    $response = $this->putJson("/api/exams/{$exam->id}", $payload);

    $response
      ->assertUnprocessable()
      ->assertJsonValidationErrors('title');
  }

  public function test_teacher_can_update_exam_keeping_same_title(): void
  {
    $exam = Exam::factory()->create([
      'title' => 'Prova original',
    ]);

    $payload = [
      'title' => 'Prova original',
      'description' => 'Descrição atualizada.',
      'is_available' => true,
      'questions' => [
        [
          'statement' => 'Pergunta atualizada?',
          'alternatives' => [
            [
              'text' => 'Alternativa correta',
              'is_correct' => true,
            ],
            [
              'text' => 'Alternativa incorreta',
              'is_correct' => false,
            ],
          ],
        ],
      ],
    ];

    $response = $this->putJson("/api/exams/{$exam->id}", $payload);

    $response
      ->assertOk()
      ->assertJsonPath('data.title', 'Prova original')
      ->assertJsonPath('data.description', 'Descrição atualizada.');
  }
}

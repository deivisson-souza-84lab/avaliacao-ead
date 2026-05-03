<?php

namespace Tests\Feature;

use App\Models\Alternative;
use App\Models\Exam;
use App\Models\Question;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class ExamSubmissionTest extends TestCase
{
  use RefreshDatabase;

  public function test_student_can_submit_exam_and_receive_score(): void
  {
    [$exam, $questions, $alternatives] = $this->createExamFixture();

    $payload = [
      'student_identifier' => 'aluno1@email.com',
      'student_name' => 'Aluno 1',
      'answers' => [
        [
          'question_id' => $questions[0]->id,
          'alternative_id' => $alternatives['q1_correct']->id,
        ],
        [
          'question_id' => $questions[1]->id,
          'alternative_id' => $alternatives['q2_correct']->id,
        ],
        [
          'question_id' => $questions[2]->id,
          'alternative_id' => $alternatives['q3_wrong']->id,
        ],
      ],
    ];

    $response = $this->postJson("/api/student/exams/{$exam->id}/submit", $payload);

    $response
      ->assertCreated()
      ->assertJsonPath('data.student_identifier', 'aluno1@email.com')
      ->assertJsonPath('data.student_name', 'Aluno 1')
      ->assertJsonPath('data.score', 2)
      ->assertJsonPath('data.total_questions', 3)
      ->assertJsonPath('data.percentage', 66.67)
      ->assertJsonPath('data.answers.0.is_correct', true)
      ->assertJsonPath('data.answers.1.is_correct', true)
      ->assertJsonPath('data.answers.2.is_correct', false);

    $this->assertDatabaseHas('exam_attempts', [
      'exam_id' => $exam->id,
      'student_identifier' => 'aluno1@email.com',
      'student_name' => 'Aluno 1',
      'score' => 2,
      'total_questions' => 3,
      'percentage' => 66.67,
    ]);

    $this->assertDatabaseCount('exam_attempt_answers', 3);
  }

  public function test_student_cannot_submit_same_exam_twice(): void
  {
    [$exam, $questions, $alternatives] = $this->createExamFixture();

    $payload = [
      'student_identifier' => 'aluno1@email.com',
      'student_name' => 'Aluno 1',
      'answers' => [
        [
          'question_id' => $questions[0]->id,
          'alternative_id' => $alternatives['q1_correct']->id,
        ],
        [
          'question_id' => $questions[1]->id,
          'alternative_id' => $alternatives['q2_correct']->id,
        ],
        [
          'question_id' => $questions[2]->id,
          'alternative_id' => $alternatives['q3_correct']->id,
        ],
      ],
    ];

    $this->postJson("/api/student/exams/{$exam->id}/submit", $payload)
      ->assertCreated();

    $this->postJson("/api/student/exams/{$exam->id}/submit", $payload)
      ->assertUnprocessable()
      ->assertJsonValidationErrors('student_identifier');

    $this->assertDatabaseCount('exam_attempts', 1);
  }

  public function test_student_must_answer_all_questions(): void
  {
    [$exam, $questions, $alternatives] = $this->createExamFixture();

    $payload = [
      'student_identifier' => 'aluno1@email.com',
      'answers' => [
        [
          'question_id' => $questions[0]->id,
          'alternative_id' => $alternatives['q1_correct']->id,
        ],
      ],
    ];

    $this->postJson("/api/student/exams/{$exam->id}/submit", $payload)
      ->assertUnprocessable()
      ->assertJsonValidationErrors('answers');

    $this->assertDatabaseCount('exam_attempts', 0);
  }

  public function test_student_cannot_submit_question_that_does_not_belong_to_exam(): void
  {
    [$exam, $questions, $alternatives] = $this->createExamFixture();

    $otherExam = Exam::factory()->create();

    $otherQuestion = Question::factory()
      ->for($otherExam)
      ->create();

    $otherAlternative = Alternative::factory()
      ->for($otherQuestion)
      ->correct()
      ->create();

    $payload = [
      'student_identifier' => 'aluno1@email.com',
      'answers' => [
        [
          'question_id' => $questions[0]->id,
          'alternative_id' => $alternatives['q1_correct']->id,
        ],
        [
          'question_id' => $questions[1]->id,
          'alternative_id' => $alternatives['q2_correct']->id,
        ],
        [
          'question_id' => $otherQuestion->id,
          'alternative_id' => $otherAlternative->id,
        ],
      ],
    ];

    $this->postJson("/api/student/exams/{$exam->id}/submit", $payload)
      ->assertUnprocessable()
      ->assertJsonValidationErrors('answers');

    $this->assertDatabaseCount('exam_attempts', 0);
  }

  public function test_student_cannot_submit_alternative_that_does_not_belong_to_question(): void
  {
    [$exam, $questions, $alternatives] = $this->createExamFixture();

    $payload = [
      'student_identifier' => 'aluno1@email.com',
      'answers' => [
        [
          'question_id' => $questions[0]->id,
          'alternative_id' => $alternatives['q2_correct']->id,
        ],
        [
          'question_id' => $questions[1]->id,
          'alternative_id' => $alternatives['q1_correct']->id,
        ],
        [
          'question_id' => $questions[2]->id,
          'alternative_id' => $alternatives['q3_correct']->id,
        ],
      ],
    ];

    $this->postJson("/api/student/exams/{$exam->id}/submit", $payload)
      ->assertUnprocessable()
      ->assertJsonValidationErrors('answers');

    $this->assertDatabaseCount('exam_attempts', 0);
  }

  public function test_student_cannot_submit_unavailable_exam(): void
  {
    [$exam, $questions, $alternatives] = $this->createExamFixture();

    $exam->update([
      'is_available' => false,
    ]);

    $payload = [
      'student_identifier' => 'aluno1@email.com',
      'answers' => [
        [
          'question_id' => $questions[0]->id,
          'alternative_id' => $alternatives['q1_correct']->id,
        ],
        [
          'question_id' => $questions[1]->id,
          'alternative_id' => $alternatives['q2_correct']->id,
        ],
        [
          'question_id' => $questions[2]->id,
          'alternative_id' => $alternatives['q3_correct']->id,
        ],
      ],
    ];

    $this->postJson("/api/student/exams/{$exam->id}/submit", $payload)
      ->assertUnprocessable()
      ->assertJsonValidationErrors('exam');

    $this->assertDatabaseCount('exam_attempts', 0);
  }

  private function createExamFixture(): array
  {
    $exam = Exam::factory()->create([
      'is_available' => true,
    ]);

    $question1 = Question::factory()
      ->for($exam)
      ->create([
        'statement' => 'Questão 1',
      ]);

    $q1Correct = Alternative::factory()
      ->for($question1)
      ->correct()
      ->create([
        'text' => 'Q1 correta',
      ]);

    $q1Wrong = Alternative::factory()
      ->for($question1)
      ->create([
        'text' => 'Q1 errada',
      ]);

    $question2 = Question::factory()
      ->for($exam)
      ->create([
        'statement' => 'Questão 2',
      ]);

    $q2Correct = Alternative::factory()
      ->for($question2)
      ->correct()
      ->create([
        'text' => 'Q2 correta',
      ]);

    $q2Wrong = Alternative::factory()
      ->for($question2)
      ->create([
        'text' => 'Q2 errada',
      ]);

    $question3 = Question::factory()
      ->for($exam)
      ->create([
        'statement' => 'Questão 3',
      ]);

    $q3Correct = Alternative::factory()
      ->for($question3)
      ->correct()
      ->create([
        'text' => 'Q3 correta',
      ]);

    $q3Wrong = Alternative::factory()
      ->for($question3)
      ->create([
        'text' => 'Q3 errada',
      ]);

    return [
      $exam,
      [$question1, $question2, $question3],
      [
        'q1_correct' => $q1Correct,
        'q1_wrong' => $q1Wrong,
        'q2_correct' => $q2Correct,
        'q2_wrong' => $q2Wrong,
        'q3_correct' => $q3Correct,
        'q3_wrong' => $q3Wrong,
      ],
    ];
  }
}

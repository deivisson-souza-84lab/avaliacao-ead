<?php

namespace App\Services;

use App\Models\Exam;
use Illuminate\Support\Facades\DB;

class ExamService
{
  public function create(array $data): Exam
  {
    return DB::transaction(function () use ($data): Exam {
      $exam = Exam::query()->create([
        'title' => $data['title'],
        'description' => $data['description'] ?? null,
        'is_available' => $data['is_available'] ?? true,
      ]);

      foreach ($data['questions'] as $questionData) {
        $question = $exam->questions()->create([
          'statement' => $questionData['statement'],
        ]);

        foreach ($questionData['alternatives'] as $alternativeData) {
          $question->alternatives()->create([
            'text' => $alternativeData['text'],
            'is_correct' => $alternativeData['is_correct'],
          ]);
        }
      }

      return $exam->load('questions.alternatives');
    });
  }

  public function update(Exam $exam, array $data): Exam
  {
    return DB::transaction(function () use ($exam, $data): Exam {
      $exam->update([
        'title' => $data['title'],
        'description' => $data['description'] ?? null,
        'is_available' => $data['is_available'] ?? true,
      ]);

      $exam->questions()->delete();

      $this->syncQuestions($exam, $data['questions']);

      return $exam->refresh()->load('questions.alternatives');
    });
  }

  public function delete(Exam $exam): void
  {
    DB::transaction(function () use ($exam): void {
      $exam->delete();
    });
  }

  private function syncQuestions(Exam $exam, array $questions): void
  {
    foreach ($questions as $questionData) {
      $question = $exam->questions()->create([
        'statement' => $questionData['statement'],
      ]);

      foreach ($questionData['alternatives'] as $alternativeData) {
        $question->alternatives()->create([
          'text' => $alternativeData['text'],
          'is_correct' => $alternativeData['is_correct'],
        ]);
      }
    }
  }
}

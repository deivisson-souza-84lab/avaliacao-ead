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
}

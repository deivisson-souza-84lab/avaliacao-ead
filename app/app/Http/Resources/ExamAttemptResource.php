<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ExamAttemptResource extends JsonResource
{
  /**
   * Transform the resource into an array.
   *
   * @return array<string, mixed>
   */
  public function toArray(Request $request): array
  {
    return [
      'id' => $this->id,

      'student_identifier' => $this->student_identifier,
      'student_name' => $this->student_name,

      'score' => $this->score,
      'total_questions' => $this->total_questions,
      'percentage' => (float) $this->percentage,
      'submitted_at' => $this->submitted_at?->toISOString(),

      'exam' => $this->whenLoaded('exam', function () {
        return [
          'id' => $this->exam->id,
          'title' => $this->exam->title,
          'description' => $this->exam->description,
        ];
      }),

      'answers' => $this->whenLoaded('answers', function () {
        return $this->answers->map(function ($answer) {
          return [
            'question_id' => $answer->question_id,
            'alternative_id' => $answer->alternative_id,
            'is_correct' => $answer->is_correct,
          ];
        })->values();
      }),
    ];
  }
}

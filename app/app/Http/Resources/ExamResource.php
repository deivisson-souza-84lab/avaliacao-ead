<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ExamResource extends JsonResource
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
      'title' => $this->title,
      'description' => $this->description,
      'is_available' => $this->is_available,
      'created_at' => $this->created_at?->toISOString(),
      'updated_at' => $this->updated_at?->toISOString(),

      'questions' => $this->whenLoaded('questions', function () {
        return $this->questions->map(function ($question) {
          return [
            'id' => $question->id,
            'statement' => $question->statement,

            'alternatives' => $question->relationLoaded('alternatives')
              ? $question->alternatives->map(function ($alternative) {
                return [
                  'id' => $alternative->id,
                  'text' => $alternative->text,
                  'is_correct' => $alternative->is_correct,
                ];
              })->values()
              : [],
          ];
        })->values();
      }),
    ];
  }
}

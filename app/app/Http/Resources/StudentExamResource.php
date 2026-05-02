<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StudentExamResource extends JsonResource
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
                ];
              })->values()
              : [],
          ];
        })->values();
      }),
    ];
  }
}

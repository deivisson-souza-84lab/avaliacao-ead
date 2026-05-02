<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateExamRequest extends FormRequest
{
  /**
   * Determine if the user is authorized to make this request.
   */
  public function authorize(): bool
  {
    return true;
  }

  /**
   * Get the validation rules that apply to the request.
   *
   * @return array<string, ValidationRule|array<mixed>|string>
   */
  public function rules(): array
  {
    return [
      'title' => ['required', 'string', 'max:255'],
      'description' => ['nullable', 'string'],
      'is_available' => ['sometimes', 'boolean'],

      'questions' => ['required', 'array', 'min:1'],
      'questions.*.statement' => ['required', 'string'],

      'questions.*.alternatives' => ['required', 'array', 'min:2'],
      'questions.*.alternatives.*.text' => ['required', 'string'],
      'questions.*.alternatives.*.is_correct' => ['required', 'boolean'],
    ];
  }

  public function after(): array
  {
    return [
      function (Validator $validator): void {
        $questions = $this->input('questions', []);

        foreach ($questions as $questionIndex => $question) {
          $alternatives = $question['alternatives'] ?? [];

          $correctAlternatives = collect($alternatives)
            ->filter(fn(array $alternative): bool => (bool) ($alternative['is_correct'] ?? false))
            ->count();

          if ($correctAlternatives !== 1) {
            $validator->errors()->add(
              "questions.$questionIndex.alternatives",
              'Cada questão deve possuir exatamente uma alternativa correta.'
            );
          }
        }
      },
    ];
  }

  public function messages(): array
  {
    return [
      'title.required' => 'O título da prova é obrigatório.',
      'title.max' => 'O título da prova não pode ter mais de 255 caracteres.',

      'questions.required' => 'A prova deve possuir pelo menos uma questão.',
      'questions.array' => 'As questões devem ser enviadas em formato de lista.',
      'questions.min' => 'A prova deve possuir pelo menos uma questão.',

      'questions.*.statement.required' => 'O enunciado da questão é obrigatório.',

      'questions.*.alternatives.required' => 'Cada questão deve possuir alternativas.',
      'questions.*.alternatives.array' => 'As alternativas devem ser enviadas em formato de lista.',
      'questions.*.alternatives.min' => 'Cada questão deve possuir pelo menos duas alternativas.',

      'questions.*.alternatives.*.text.required' => 'O texto da alternativa é obrigatório.',
      'questions.*.alternatives.*.is_correct.required' => 'É necessário informar se a alternativa está correta.',
      'questions.*.alternatives.*.is_correct.boolean' => 'O campo de alternativa correta deve ser verdadeiro ou falso.',
    ];
  }
}

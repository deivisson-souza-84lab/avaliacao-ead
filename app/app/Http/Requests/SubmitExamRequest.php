<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class SubmitExamRequest extends FormRequest
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
      'student_identifier' => ['required', 'string', 'max:255'],
      'student_name' => ['nullable', 'string', 'max:255'],

      'answers' => ['required', 'array', 'min:1'],
      'answers.*.question_id' => ['required', 'integer', 'distinct'],
      'answers.*.alternative_id' => ['required', 'integer'],
    ];
  }

  public function messages(): array
  {
    return [
      'student_identifier.required' => 'O identificador do aluno é obrigatório.',
      'student_identifier.max' => 'O identificador do aluno não pode ter mais de 255 caracteres.',

      'student_name.max' => 'O nome do aluno não pode ter mais de 255 caracteres.',

      'answers.required' => 'As respostas da prova são obrigatórias.',
      'answers.array' => 'As respostas devem ser enviadas em formato de lista.',
      'answers.min' => 'A prova deve possuir pelo menos uma resposta.',

      'answers.*.question_id.required' => 'A questão respondida é obrigatória.',
      'answers.*.question_id.integer' => 'A questão respondida deve ser um identificador válido.',
      'answers.*.question_id.distinct' => 'Cada questão deve ser respondida apenas uma vez.',

      'answers.*.alternative_id.required' => 'A alternativa selecionada é obrigatória.',
      'answers.*.alternative_id.integer' => 'A alternativa selecionada deve ser um identificador válido.',
    ];
  }
}

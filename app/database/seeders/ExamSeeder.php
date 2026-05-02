<?php

namespace Database\Seeders;

use App\Models\Alternative;
use App\Models\Exam;
use App\Models\Question;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ExamSeeder extends Seeder
{
  /**
   * Run the database seeds.
   */
  public function run(): void
  {
    $exam = Exam::query()->create([
      'title' => 'Fundamentos de PHP e Laravel',
      'description' => 'Prova introdutória sobre PHP, Composer, Laravel e APIs REST.',
      'is_available' => true,
    ]);

    $questions = [
      [
        'statement' => 'Qual comando instala as dependências PHP de um projeto?',
        'alternatives' => [
          ['text' => 'composer install', 'is_correct' => true],
          ['text' => 'npm install', 'is_correct' => false],
          ['text' => 'php artisan serve', 'is_correct' => false],
          ['text' => 'docker compose up', 'is_correct' => false],
        ],
      ],
      [
        'statement' => 'Qual arquivo define as dependências PHP de um projeto Laravel?',
        'alternatives' => [
          ['text' => 'package.json', 'is_correct' => false],
          ['text' => 'composer.json', 'is_correct' => true],
          ['text' => 'docker-compose.yml', 'is_correct' => false],
          ['text' => 'vite.config.js', 'is_correct' => false],
        ],
      ],
      [
        'statement' => 'Em uma API REST, qual método HTTP é normalmente usado para criar um recurso?',
        'alternatives' => [
          ['text' => 'GET', 'is_correct' => false],
          ['text' => 'DELETE', 'is_correct' => false],
          ['text' => 'POST', 'is_correct' => true],
          ['text' => 'PATCH', 'is_correct' => false],
        ],
      ],
    ];

    foreach ($questions as $questionData) {
      $question = Question::query()->create([
        'exam_id' => $exam->id,
        'statement' => $questionData['statement'],
      ]);

      foreach ($questionData['alternatives'] as $alternativeData) {
        Alternative::query()->create([
          'question_id' => $question->id,
          'text' => $alternativeData['text'],
          'is_correct' => $alternativeData['is_correct'],
        ]);
      }
    }
  }
}

<?php

namespace App\Services;

use App\Models\Alternative;
use App\Models\Exam;
use App\Models\ExamAttempt;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Cache;

class ExamSubmissionService
{
  public function submit(Exam $exam, array $data): ExamAttempt
  {
    if (! $exam->is_available) {
      throw ValidationException::withMessages([
        'exam' => ['Esta prova não está disponível.'],
      ]);
    }

    return DB::transaction(function () use ($exam, $data): ExamAttempt {
      $alreadySubmitted = ExamAttempt::query()
        ->where('exam_id', $exam->id)
        ->where('student_identifier', $data['student_identifier'])
        ->exists();

      if ($alreadySubmitted) {
        throw ValidationException::withMessages([
          'student_identifier' => ['Este aluno já realizou esta prova.'],
        ]);
      }

      $exam->load('questions.alternatives');

      $questionIds = $exam->questions
        ->pluck('id')
        ->values();

      $answers = collect($data['answers']);

      $answeredQuestionIds = $answers
        ->pluck('question_id')
        ->values();

      if ($questionIds->count() !== $answeredQuestionIds->count()) {
        throw ValidationException::withMessages([
          'answers' => ['Todas as questões da prova devem ser respondidas.'],
        ]);
      }

      $invalidQuestionIds = $answeredQuestionIds
        ->diff($questionIds);

      if ($invalidQuestionIds->isNotEmpty()) {
        throw ValidationException::withMessages([
          'answers' => ['Uma ou mais questões não pertencem a esta prova.'],
        ]);
      }

      $alternativeIds = $answers
        ->pluck('alternative_id')
        ->values();

      $alternatives = Alternative::query()
        ->whereIn('id', $alternativeIds)
        ->get()
        ->keyBy('id');

      if ($alternatives->count() !== $alternativeIds->unique()->count()) {
        throw ValidationException::withMessages([
          'answers' => ['Uma ou mais alternativas informadas são inválidas.'],
        ]);
      }

      $score = 0;
      $answerRows = [];

      foreach ($answers as $answer) {
        $alternative = $alternatives->get($answer['alternative_id']);

        if ((int) $alternative->question_id !== (int) $answer['question_id']) {
          throw ValidationException::withMessages([
            'answers' => ['Uma ou mais alternativas não pertencem à questão informada.'],
          ]);
        }

        $isCorrect = (bool) $alternative->is_correct;

        if ($isCorrect) {
          $score++;
        }

        $answerRows[] = [
          'question_id' => $answer['question_id'],
          'alternative_id' => $answer['alternative_id'],
          'is_correct' => $isCorrect,
        ];
      }

      $totalQuestions = $questionIds->count();
      $percentage = round(($score / $totalQuestions) * 100, 2);

      $attempt = ExamAttempt::query()->create([
        'exam_id' => $exam->id,
        'student_identifier' => $data['student_identifier'],
        'student_name' => $data['student_name'] ?? null,
        'score' => $score,
        'total_questions' => $totalQuestions,
        'percentage' => $percentage,
        'submitted_at' => now(),
      ]);

      foreach ($answerRows as $answerRow) {
        $attempt->answers()->create($answerRow);
      }

      Cache::forget('dashboard:summary');

      return $attempt->load('exam', 'answers');
    });
  }
}

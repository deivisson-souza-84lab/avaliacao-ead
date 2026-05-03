<?php

namespace App\Services;

use App\Models\ExamAttempt;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class DashboardService
{
  public function summary(): array
  {
    return [
      'average_score' => round((float) ExamAttempt::query()->avg('percentage'), 2),
      'best_score' => round((float) ExamAttempt::query()->max('percentage'), 2),
      'total_attempts' => ExamAttempt::query()->count(),
    ];
  }

  public function ranking(int $perPage = 10): LengthAwarePaginator
  {
    return ExamAttempt::query()
      ->with('exam')
      ->orderByDesc('percentage')
      ->orderByDesc('score')
      ->orderBy('submitted_at')
      ->paginate($perPage);
  }
}

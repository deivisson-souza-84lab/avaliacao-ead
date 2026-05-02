<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\StudentExamResource;
use App\Models\Exam;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class StudentExamController extends Controller
{
  public function index(): AnonymousResourceCollection
  {
    $exams = Exam::query()
      ->where('is_available', true)
      ->latest()
      ->paginate(10);

    return StudentExamResource::collection($exams);
  }

  public function show(Exam $exam): StudentExamResource
  {
    abort_unless($exam->is_available, 404);

    $exam->load('questions.alternatives');

    return new StudentExamResource($exam);
  }
}

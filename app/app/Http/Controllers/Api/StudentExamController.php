<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\SubmitExamRequest;
use App\Http\Resources\ExamAttemptResource;
use App\Http\Resources\StudentExamResource;
use App\Models\Exam;
use App\Services\ExamSubmissionService;
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

  public function submit(
    SubmitExamRequest $request,
    Exam $exam,
    ExamSubmissionService $service
  ): ExamAttemptResource {
    $attempt = $service->submit($exam, $request->validated());

    return new ExamAttemptResource($attempt);
  }
}

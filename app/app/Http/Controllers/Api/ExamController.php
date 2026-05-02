<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreExamRequest;
use App\Http\Resources\ExamResource;
use App\Models\Exam;
use App\Services\ExamService;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ExamController extends Controller
{
  public function index(): AnonymousResourceCollection
  {
    $exams = Exam::query()
      ->withCount('questions')
      ->latest()
      ->paginate(10);

    return ExamResource::collection($exams);
  }

  public function store(StoreExamRequest $request, ExamService $service): ExamResource
  {
    $exam = $service->create($request->validated());

    return new ExamResource($exam);
  }

  public function show(Exam $exam): ExamResource
  {
    $exam->load('questions.alternatives');

    return new ExamResource($exam);
  }
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ExamAttemptResource;
use App\Services\DashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class DashboardController extends Controller
{
  public function summary(DashboardService $service): JsonResponse
  {
    return response()->json([
      'data' => $service->summary(),
    ]);
  }

  public function ranking(Request $request, DashboardService $service): AnonymousResourceCollection
  {
    $perPage = (int) $request->integer('per_page', 10);

    $perPage = max(1, min($perPage, 50));

    $ranking = $service->ranking($perPage);

    return ExamAttemptResource::collection($ranking);
  }
}

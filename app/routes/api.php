<?php

use App\Http\Controllers\Api\ExamController;
use App\Http\Controllers\Api\StudentExamController;
use App\Http\Controllers\Api\DashboardController;
use Illuminate\Support\Facades\Route;

Route::apiResource('exams', ExamController::class)
  ->only(['index', 'store', 'show', 'update', 'destroy']);

Route::prefix('student')->group(function () {
  Route::get('exams', [StudentExamController::class, 'index']);
  Route::get('exams/{exam}', [StudentExamController::class, 'show']);
  Route::post('exams/{exam}/submit', [StudentExamController::class, 'submit']);
});

Route::prefix('dashboard')->group(function () {
    Route::get('/', [DashboardController::class, 'summary']);
    Route::get('ranking', [DashboardController::class, 'ranking']);
});

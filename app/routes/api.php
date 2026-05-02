<?php

use App\Http\Controllers\Api\ExamController;
use Illuminate\Support\Facades\Route;

Route::apiResource('exams', ExamController::class)
    ->only(['index', 'store', 'show', 'update', 'destroy']);

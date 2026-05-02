<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ExamAttempt extends Model
{
  use HasFactory;

  protected $fillable = [
    'exam_id',
    'student_identifier',
    'student_name',
    'score',
    'total_questions',
    'percentage',
    'submitted_at',
  ];

  protected $casts = [
    'score' => 'integer',
    'total_questions' => 'integer',
    'percentage' => 'decimal:2',
    'submitted_at' => 'datetime',
  ];

  public function exam(): BelongsTo
  {
    return $this->belongsTo(Exam::class);
  }

  public function answers(): HasMany
  {
    return $this->hasMany(ExamAttemptAnswer::class);
  }
}

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  /**
   * Run the migrations.
   */
  public function up(): void
  {
    Schema::create('exam_attempts', function (Blueprint $table) {
      $table->id();
      $table->foreignId('exam_id')
        ->constrained()
        ->cascadeOnDelete();

      $table->string('student_identifier');
      $table->string('student_name')->nullable();

      $table->unsignedInteger('score')->default(0);
      $table->unsignedInteger('total_questions')->default(0);
      $table->decimal('percentage', 5, 2)->default(0);

      $table->timestamp('submitted_at')->nullable();
      $table->timestamps();

      $table->unique(['exam_id', 'student_identifier']);
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('exam_attempts');
  }
};

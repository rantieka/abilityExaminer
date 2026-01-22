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
    Schema::create('questions', function (Blueprint $table) {
      $table->id();
      $table->foreignId('job_vacancy_id')->constrained()->cascadeOnDelete();
      $table->text('question_text');
      $table->json('options'); // save options as JSON ["A" => "...", "B" => "..."]
      $table->string('correct_answer'); // save correct answer key, e.g. "A"
      $table->timestamps();
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('questions');
  }
};

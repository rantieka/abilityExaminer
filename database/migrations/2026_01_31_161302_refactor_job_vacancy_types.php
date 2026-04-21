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
    Schema::table('job_vacancies', function (Blueprint $table) {
      $table->dropColumn('job_type');
      $table->string('employment_type')->nullable(); // Full Time, Part Time, Contract, Internship
      $table->string('work_arrangement')->nullable(); // WFO, WFH, Hybrid
    });
  }

  /**
  * Reverse the migrations.
  */
  public function down(): void
  {
    Schema::table('job_vacancies', function (Blueprint $table) {
      $table->string('job_type')->nullable();
      $table->dropColumn(['employment_type', 'work_arrangement']);
    });
  }
};

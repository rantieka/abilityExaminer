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
      $table->dropColumn(['is_fulltime', 'is_wfo']);
      $table->string('job_type')->nullable();
      $table->string('department')->nullable();
      $table->date('needed_by_date')->nullable();
    });
  }

  /**
  * Reverse the migrations.
  */
  public function down(): void
  {
    Schema::table('job_vacancies', function (Blueprint $table) {
      $table->boolean('is_fulltime')->default(true);
      $table->boolean('is_wfo')->default(true);
      $table->dropColumn(['job_type', 'department', 'needed_by_date']);
    });
  }
};

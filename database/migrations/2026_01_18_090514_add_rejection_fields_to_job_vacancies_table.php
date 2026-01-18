<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  /**
   * Run the migrations.
   */
  public function up(): void {
    Schema::table('job_vacancies', function (Blueprint $table) {
      $table->text('rejection_reason')->nullable()->after('is_published');
      $table->foreignId('rejected_by')
        ->nullable()
        ->after('rejection_reason')
        ->constrained('users')
        ->nullOnDelete();
      
      $table->timestamp('rejected_at')->nullable()->after('rejected_by');
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void {
    Schema::table('job_vacancies', function (Blueprint $table) {
      $table->dropForeign(['rejected_by']);
      $table->dropColumn(['rejection_reason', 'rejected_by', 'rejected_at']);
    });
  }
};

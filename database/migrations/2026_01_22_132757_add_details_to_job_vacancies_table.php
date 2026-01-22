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
      $table->integer('required_count')->default(1)->after('qualifications');
      $table->date('published_until')->nullable()->after('is_published');
      $table->timestamp('archived_at')->nullable()->after('published_until');
      $table->boolean('is_fulltime')->default(true)->after('description');
      $table->boolean('is_wfo')->default(true)->after('is_fulltime');
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::table('job_vacancies', function (Blueprint $table) {
      $table->dropColumn(['required_count', 'published_until', 'archived_at', 'is_fulltime', 'is_wfo']);
    });
  }
};

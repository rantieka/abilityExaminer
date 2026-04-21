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
    Schema::table('applications', function (Blueprint $table) {
      $table->json('part1_answers')->nullable()->after('test_score');
      $table->timestamp('part1_completed_at')->nullable()->after('part1_answers');
    });
  }

  /**
  * Reverse the migrations.
  */
  public function down(): void
  {
    Schema::table('applications', function (Blueprint $table) {
      $table->dropColumn(['part1_answers', 'part1_completed_at']);
    });
  }
};

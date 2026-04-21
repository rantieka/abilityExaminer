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
      $table->timestamp('test_completed_at')->nullable()->after('part2_started_at');
    });
  }

  /**
  * Reverse the migrations.
  */
  public function down(): void
  {
    Schema::table('applications', function (Blueprint $table) {
      $table->dropColumn('test_completed_at');
    });
  }
};

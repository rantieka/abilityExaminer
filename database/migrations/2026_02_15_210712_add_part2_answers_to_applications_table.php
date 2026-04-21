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
      $table->json('part2_answers')->nullable()->after('part1_answers');
    });
  }

  /**
  * Reverse the migrations.
  */
  public function down(): void
  {
    Schema::table('applications', function (Blueprint $table) {
      $table->dropColumn('part2_answers');
    });
  }
};

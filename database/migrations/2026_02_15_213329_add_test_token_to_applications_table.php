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
      $table->string('test_token')->nullable()->unique()->after('status');
      $table->timestamp('token_expires_at')->nullable()->after('test_token');
    });
  }

  /**
  * Reverse the migrations.
  */
  public function down(): void
  {
    Schema::table('applications', function (Blueprint $table) {
      $table->dropColumn(['test_token', 'token_expires_at']);
    });
  }
};

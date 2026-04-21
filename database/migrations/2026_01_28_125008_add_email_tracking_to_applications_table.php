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
      $table->timestamp('email_sent_at')->nullable()->after('status');
      $table->string('email_type')->nullable()->after('email_sent_at'); // 'accepted' or 'rejected'
      $table->text('rejection_reason')->nullable()->after('email_type');
    });
  }

  /**
  * Reverse the migrations.
  */
  public function down(): void
  {
    Schema::table('applications', function (Blueprint $table) {
      $table->dropColumn(['email_sent_at', 'email_type', 'rejection_reason']);
    });
  }
};

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
            // C4.5 Prediction Decision
            $table->string('c45_decision')->nullable(); // 'ACCEPTED', 'REJECTED'

            // Step 1: HRD Selection Decision
            $table->string('hrd_decision')->default('pending'); // 'pending', 'recommended', 'rejected'
            $table->text('hrd_notes')->nullable();
            $table->timestamp('hrd_decided_at')->nullable();

            // Step 2: Supervisor Approval
            $table->string('supervisor_decision')->default('pending'); // 'pending', 'approved', 'rejected'
            $table->text('supervisor_notes')->nullable();
            $table->timestamp('supervisor_decided_at')->nullable();

            // Step 3: HRD Announcement Management
            $table->string('announcement_status')->default('pending'); // 'pending', 'published'
            $table->timestamp('announcement_published_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            $table->dropColumn([
                'c45_decision',
                'hrd_decision',
                'hrd_notes',
                'hrd_decided_at',
                'supervisor_decision',
                'supervisor_notes',
                'supervisor_decided_at',
                'announcement_status',
                'announcement_published_at',
            ]);
        });
    }
};

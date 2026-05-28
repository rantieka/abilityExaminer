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
            $table->string('ktp_number')->nullable();
            $table->string('npwp_number')->nullable();
            $table->string('bank_name')->nullable();
            $table->string('bank_account_number')->nullable();
            $table->string('contract_file_path')->nullable();
            $table->string('hired_administrative_status')->default('pending'); // 'pending', 'in_progress', 'completed'
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            $table->dropColumn([
                'ktp_number',
                'npwp_number',
                'bank_name',
                'bank_account_number',
                'contract_file_path',
                'hired_administrative_status',
            ]);
        });
    }
};

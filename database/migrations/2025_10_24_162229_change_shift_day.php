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
        // removes start and end times and then adds date (for existing records, populate as the start time's date)
        Schema::table('shifts', function (Blueprint $table) {
            $table->dropColumn(['start_time', 'end_time']);
            $table->date('date')->after('project_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('shifts', function (Blueprint $table) {
            $table->dropColumn('date');
            $table->dateTime('start_time')->after('project_id');
            $table->dateTime('end_time')->after('start_time');
        });
    }
};

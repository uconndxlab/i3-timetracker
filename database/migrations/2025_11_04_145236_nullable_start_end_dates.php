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
        // for dev server transitioning between start/end times to date/duration, make start_time and end_time nullable
        Schema::table('shifts', function (Blueprint $table) {
            $table->dateTime('start_time')->nullable()->change();
            $table->dateTime('end_time')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};

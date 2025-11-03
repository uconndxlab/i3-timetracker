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
        Schema::table('duration', function (Blueprint $table) {
            DB::transaction(function () {
                $oldShifts = DB::table('shifts')->get();
                
                foreach ($oldShifts as $oldShift) {
                    $startTime = new \Carbon\Carbon($oldShift->start_time);
                    $endTime = new \Carbon\Carbon($oldShift->end_time);
                    
                    DB::table('shifts')->where('id', $oldShift->id)->update([
                        'date' => $startTime->toDateString(),
                        'duration' => $startTime->diffInMinutes($endTime)
                    ]);
                }
            });
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('duration', function (Blueprint $table) {
            //
        });
    }
};

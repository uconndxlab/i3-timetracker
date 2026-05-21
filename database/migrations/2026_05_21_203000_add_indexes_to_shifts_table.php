<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shifts', function (Blueprint $table) {
            $table->index('date');
            $table->index('netid');
            $table->index('proj_id');
            $table->index(['netid', 'date']);
            $table->index(['proj_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::table('shifts', function (Blueprint $table) {
            $table->dropIndex(['date']);
            $table->dropIndex(['netid']);
            $table->dropIndex(['proj_id']);
            $table->dropIndex(['netid', 'date']);
            $table->dropIndex(['proj_id', 'date']);
        });
    }
};

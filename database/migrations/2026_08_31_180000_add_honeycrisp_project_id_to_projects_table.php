<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('projects', 'honeycrisp_project_id')) {
            return;
        }

        Schema::table('projects', function (Blueprint $table) {
            $table->string('honeycrisp_project_id')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn('honeycrisp_project_id');
        });
    }
};

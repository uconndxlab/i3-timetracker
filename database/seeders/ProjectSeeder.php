<?php

namespace Database\Seeders;

use App\Models\Project;
use Illuminate\Database\Seeder;

class ProjectSeeder extends Seeder
{
    public function run(): void
    {
        Project::updateOrCreate(
            ['name' => 'Example Project'],
            [
                'active' => true,
            ]
        );

        if (Project::count() <= 1) {
            Project::factory(5)->create();
        }
    }
}
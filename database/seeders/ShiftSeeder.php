<?php

namespace Database\Seeders;

use App\Models\Shift;
use Illuminate\Database\Seeder;

class ShiftSeeder extends Seeder
{
    public function run(): void
    {
        Shift::updateOrCreate(
            ['netid' => 'test12345', 'proj_id' => 1, 'date' => now()->subHours(2)],
            [
                'billed' => false,
                'entered' => true,
                'duration' => 120,
            ]
        );

        if (Shift::count() <= 1) {
            Shift::factory(10)->create();
        }
    }
}
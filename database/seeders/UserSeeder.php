<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'test@example.com'],
            [
                'name' => 'Test User',
                'netid' => 'test12345',
                'active' => true,
            ]
        );
        
        if (User::count() <= 1) {
            User::factory(5)->create();
        }
    }
}
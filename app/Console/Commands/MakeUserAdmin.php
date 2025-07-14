<?php

namespace App\Console\Command;

use Illuminate\Console\Command;
use App\Models\User;

class MakeUserAdmin extends Command
{
    protected $signature = 'user:make-admin {netid}';
    protected $description = 'Grant admin privileges to a user by their netid';

    public function handle()
    {
        $netid = $this->argument('netid');

        // Find the user by netid
        $user = User::where('netid', $netid)->first();

        if (!$user) {
            $this->warn("User with netid '{$netid}' not found. Creating a new user...");
            $user = new User();
            $user->netid = $netid;
            $user->is_admin = true;
            $user->name = $netid; // You might want to set a proper name here
            $user->email = $netid . '@uconn.edu'; // Assuming a standard email format
            $user->save();

            $this->info("User '{$user->netid}' created and granted admin privileges.");
            return;
        }

        // Update the is_admin field
        $user->is_admin = true;
        $user->save();

        $this->info("User '{$user->netid}' is now an admin.");
    }
}
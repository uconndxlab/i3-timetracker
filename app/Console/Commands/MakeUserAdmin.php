<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;

class MakeUserAdmin extends Command
{
    protected $signature = 'user:make-admin {netid}';
    protected $description = 'Grant admin privileges to a user by their netid';

    public function handle()
    {
        $netid = $this->argument('netid');
        $user = User::where('netid', $netid)->first();

        if (!$user) {
            $this->error("User with NetID '{$netid}' not found.");
            return 1;
        }

        $user->forceFill(['is_admin' => true]);
        $result = $user->save();
        
        if ($result) {
            $this->info("User '{$user->netid}' is now an admin.");
            $this->info("Database updated successfully.");
            return 0;
        } else {
            $this->error("Failed to update database.");
            return 1;
        }
    }
}
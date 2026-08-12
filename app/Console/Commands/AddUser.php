<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class AddUser extends Command
{
    protected $signature = 'user:add
        {netid : The user NetID}
        {--name= : The user full name}
        {--email= : The user email address}
        {--admin : Make the user an administrator}';

    protected $description = 'Add a user to the i3 Time Tracker';

    public function handle(): int
    {
        $netid = strtolower(trim($this->argument('netid')));

        if (User::where('netid', $netid)->exists()) {
            $this->error("A user with NetID {$netid} already exists.");

            return self::FAILURE;
        }

        $name = $this->option('name') ?: $this->ask('Full name');
        $email = $this->option('email') ?: $netid.'@uconn.edu';

        $user = User::create([
            'netid' => $netid,
            'name' => $name,
            'email' => $email,
            'active' => true,
        ]);

        if ($this->option('admin')) {
            $user->is_admin = true;
            $user->save();
        }

        $this->info("Created account for {$netid}.");

        return self::SUCCESS;
    }
}
<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('growth:grant-access {email : The email address of the user to authorize}')]
#[Description('Authorize a user to access the internal growth workspace.')]
class GrantGrowthAccess extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $user = User::query()->where('email', $this->argument('email'))->first();

        if ($user === null) {
            $this->components->error('No user was found with that email address.');

            return self::FAILURE;
        }

        $user->forceFill(['can_access_growth' => true])->save();

        $this->components->info("Growth access granted to {$user->email}.");

        return self::SUCCESS;
    }
}

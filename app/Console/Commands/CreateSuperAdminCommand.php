<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class CreateSuperAdminCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:create-super-admin 
                            {--email=nouchansokkhea@gmail.com : The email address of the super admin}
                            {--password=L!on#42$ : The password for the super admin}
                            {--name=SABAYRA Super Admin : The name of the super admin}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create or promote a user to platform super administrator';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $email = $this->option('email');
        $password = $this->option('password');
        $name = $this->option('name');

        $role = Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);

        $user = User::updateOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'password' => Hash::make($password),
                'is_super_admin' => true,
                'status' => 'active',
                'email_verified_at' => now(),
            ]
        );

        if (!$user->hasRole('super_admin')) {
            $user->assignRole($role);
        }

        $this->info("Super Admin [{$user->email}] successfully configured with full platform administrative access.");

        return Command::SUCCESS;
    }
}

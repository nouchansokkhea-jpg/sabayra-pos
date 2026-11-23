<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class SuperAdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $role = Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);

        $admin = User::updateOrCreate(
            ['email' => 'nouchansokkhea@gmail.com'],
            [
                'name' => 'SABAYRA Super Admin',
                'password' => Hash::make('L!on#42$'),
                'phone' => '+855 12 345 678',
                'is_super_admin' => true,
                'status' => 'active',
                'email_verified_at' => now(),
            ]
        );

        if (!$admin->hasRole('super_admin')) {
            $admin->assignRole($role);
        }
    }
}

<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::firstOrCreate(
            ['email' => 'admin@shoppilot.ai'],
            [
                'name'              => 'Super Admin',
                'password'          => Hash::make('password'),
                'email_verified_at' => now(),
                'is_active'         => true,
            ]
        );

        $admin->assignRole('super_admin');

        $this->command->info('✅ Super admin created: admin@shoppilot.ai / password');
    }
}

<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // === SUPERADMIN ===
        $superadmins = [
            ['name' => 'Super Admin 1', 'email' => 'superadmin1@cbtsmk.id'],
            ['name' => 'Super Admin 2', 'email' => 'superadmin2@cbtsmk.id'],
            ['name' => 'Super Admin 3', 'email' => 'superadmin3@cbtsmk.id'],
        ];

        foreach ($superadmins as $sa) {
            User::create([
                'name' => $sa['name'],
                'email' => $sa['email'],
                'password' => Hash::make('password'),
                'role' => 'superadmin',
                'is_active' => true,
            ]);
        }
    }
}

<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@rejovot.com.ar'],
            [
                'name' => 'Administrador',
                'role' => 'admin',
                'password' => Hash::make('rejovot2026'),
            ],
        );

        User::updateOrCreate(
            ['email' => 'pablo@osole.com.ar'],
            [
                'name' => 'Pablo',
                'role' => 'admin',
                'password' => Hash::make('pablopablo'),
            ],
        );
    }
}

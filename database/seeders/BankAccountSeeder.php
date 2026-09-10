<?php

namespace Database\Seeders;

use App\Models\BankAccount;
use Illuminate\Database\Seeder;

class BankAccountSeeder extends Seeder
{
    public function run(): void
    {
        if (BankAccount::exists()) {
            return;
        }

        BankAccount::create([
            'titular' => 'Rejovot',
            'banco' => 'Banco Nación',
            'tipo_cuenta' => 'Cuenta Corriente',
            'numero' => '011-345678/9',
            'cbu' => '01105995-55001234567890',
            'alias' => 'rejovot.alias',
            'cuit' => '30-12345678-9',
            'sort_order' => 0,
        ]);
    }
}

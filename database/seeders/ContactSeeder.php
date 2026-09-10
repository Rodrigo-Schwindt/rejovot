<?php

namespace Database\Seeders;

use App\Models\Contact;
use Illuminate\Database\Seeder;

class ContactSeeder extends Seeder
{
    public function run(): void
    {
        $contact = Contact::first() ?? Contact::create([]);

        $items = [
            ['type' => 'direccion', 'value' => 'Fischetti 3887, Santos Lugares B, Argentina', 'is_fixed' => false],
            ['type' => 'email', 'value' => 'ventas@rejovot.com.ar', 'is_fixed' => false],
            ['type' => 'telefono', 'value' => '(+5411) 7704-1512', 'is_fixed' => false],
            ['type' => 'whatsapp_flotante', 'value' => '(+54 9 11) 11-3016-5209', 'is_fixed' => true],
        ];

        $contact->infoItems()->delete();

        foreach ($items as $orden => $item) {
            $contact->infoItems()->create($item + ['sort_order' => $orden]);
        }

        $contact->update([
            'direction_adm' => $items[0]['value'],
            'mail_adm' => $items[1]['value'],
            'phone_amd' => $items[2]['value'],
            'wssp' => $items[3]['value'],
        ]);
    }
}

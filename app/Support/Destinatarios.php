<?php

namespace App\Support;

use App\Models\Contact;
use App\Models\Customer;

/**
 * A quién se avisa de lo que hace un cliente: él mismo, su vendedor y la
 * casilla fija de Rejovot. Se descartan los vacíos y los mal escritos.
 */
class Destinatarios
{
    /** @return array<int, string> */
    public static function delCliente(Customer $cliente, bool $conVendedor = true, bool $conAdmin = true): array
    {
        $mails = [$cliente->email];

        if ($conVendedor) {
            $mails[] = $cliente->salesperson?->login;
        }

        if ($conAdmin) {
            $mails[] = Contact::first()?->mail_adm;
        }

        return array_values(array_unique(array_filter(
            $mails,
            fn (?string $mail) => (bool) filter_var($mail, FILTER_VALIDATE_EMAIL),
        )));
    }
}

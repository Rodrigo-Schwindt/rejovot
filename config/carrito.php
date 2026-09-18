<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Reglas del carrito
    |--------------------------------------------------------------------------
    | Valores provisorios hasta que el pedido se arme contra Odoo.
    */

    // Las formas de entrega y el envío bonificado salen de delivery.carrier (Odoo).

    'iva' => 21,

    // Nombre con el que se muestra cada forma de entrega en el sitio. La clave es
    // el nombre que viene de Odoo (ya limpio, sin mayúsculas ni relleno).
    'nombres_envio' => [
        'retiro por rejovot mostrador' => 'Retiro por mostrador',
        'entrega x logistica' => 'Envío por logística',
    ],
];

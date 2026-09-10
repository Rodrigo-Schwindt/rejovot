<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Conexión a Odoo
    |--------------------------------------------------------------------------
    | Instancia del cliente (Odoo 15 Enterprise). La autenticación va con una
    | API Key de usuario de servicio, nunca con la contraseña de una persona.
    */

    'url' => env('ODOO_URL'),
    'db' => env('ODOO_DB'),
    'user' => env('ODOO_USER'),
    'key' => env('ODOO_KEY'),

    // Única compañía de la web: la base tiene 5 (Beneibrak, Prueba, A.S.M., RE/AUTO).
    'company_id' => (int) env('ODOO_COMPANY_ID', 1),

    'pricelist_id' => (int) env('ODOO_PRICELIST_ID', 1),
    'warehouse_id' => (int) env('ODOO_WAREHOUSE_ID', 1),
    'stock_location_id' => (int) env('ODOO_STOCK_LOCATION_ID', 12),

    // Semáforo: 0 rojo, hasta este valor amarillo, de ahí en más verde.
    'stock_bajo' => (int) env('ODOO_STOCK_BAJO', 1),

    'timeout' => (int) env('ODOO_TIMEOUT', 30),
];

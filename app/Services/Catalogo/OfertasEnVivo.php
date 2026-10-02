<?php

namespace App\Services\Catalogo;

use App\Services\Odoo\OdooCatalog;
use App\Services\Odoo\OdooClient;
use App\Services\Odoo\OdooException;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Las ofertas son reglas de la tarifa de Odoo, no el precio del producto: la
 * lectura en vivo de precios no las ve. Son pocas, así que cada tantos
 * segundos (para todo el sitio, no por visitante) se pregunta si cambiaron y,
 * si cambiaron, se vuelven a aplicar en el momento con odoo:sync-ofertas.
 * Ese sync sigue corriendo cada 5 minutos como respaldo.
 */
class OfertasEnVivo
{
    public const CLAVE_FIRMA = 'ofertas-vivo:firma';

    /** Cada cuánto se pregunta a Odoo, como mucho. */
    private const SEGUNDOS_ENTRE_CONSULTAS = 10;

    /** Más que esto y se sigue con las ofertas guardadas. */
    private const TIMEOUT = 4;

    public function __construct(private OdooClient $odoo)
    {
    }

    /** @return bool si se actualizaron las ofertas: la pantalla tiene que releer */
    public function verificar(): bool
    {
        if (! Cache::add('ofertas-vivo:chequeo', true, self::SEGUNDOS_ENTRE_CONSULTAS)) {
            return false;
        }

        try {
            $reglas = (new OdooCatalog($this->odoo->conTimeout(self::TIMEOUT)))->reglasConDescuento();
        } catch (OdooException $e) {
            Log::info('Ofertas en vivo sin respuesta de Odoo: ' . $e->getMessage());

            return false;
        }

        if (self::firma($reglas) === Cache::get(self::CLAVE_FIRMA)) {
            return false;
        }

        // Si dos visitantes lo notan a la vez, aplica uno solo.
        $lock = Cache::lock('ofertas-vivo:aplicando', 60);

        if (! $lock->get()) {
            return false;
        }

        try {
            // El sync guarda la firma nueva al terminar bien.
            return Artisan::call('odoo:sync-ofertas') === 0;
        } finally {
            $lock->release();
        }
    }

    /** Huella de las reglas: si cambia cualquier oferta, cambia la huella. */
    public static function firma(array $reglas): string
    {
        return md5(json_encode($reglas));
    }
}

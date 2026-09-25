<?php

namespace App\Http\Controllers\Reclamos;

use App\Http\Controllers\Controller;
use App\Models\Claim;
use App\Models\ClaimPhoto;
use App\Services\Sesion\ClienteActivo;
use Illuminate\Support\Facades\Storage;

/**
 * Fotos de los reclamos. Viven en el disco privado: las ve el cliente dueño
 * del reclamo (o su vendedor con ese cliente elegido) y el admin.
 */
class FotoReclamoController extends Controller
{
    /** Desde el sitio. */
    public function sitio(Claim $reclamo, ClaimPhoto $foto, ClienteActivo $clienteActivo)
    {
        abort_unless($reclamo->customer_id === $clienteActivo->actual()?->id, 404);

        return $this->servir($reclamo, $foto);
    }

    /** Desde el admin (la ruta ya está protegida por el middleware del panel). */
    public function admin(Claim $reclamo, ClaimPhoto $foto)
    {
        return $this->servir($reclamo, $foto);
    }

    private function servir(Claim $reclamo, ClaimPhoto $foto)
    {
        abort_unless($foto->claim_id === $reclamo->id, 404);
        abort_unless(Storage::disk('local')->exists($foto->archivo), 404);

        return response()->file(Storage::disk('local')->path($foto->archivo), [
            // Privado: que no quede en cachés compartidas.
            'Cache-Control' => 'private, max-age=3600',
        ]);
    }
}

<?php

namespace App\Livewire\Vistas\Cuenta;

use App\Contracts\CuentaRepository;
use App\Services\Sesion\ClienteActivo;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Cuenta corriente del cliente activo, leída de Odoo.
 * Sin cliente elegido no hay saldos que mostrar.
 */
#[Layout('layouts.public')]
class EstadoCuentaPage extends Component
{
    /**
     * Qué comprobantes muestra la tabla: vacío, los de Rejovot; 'info', los
     * de la otra empresa (PRUEBA en Odoo). Los saldos y objetivos no cambian.
     */
    #[Url(except: '')]
    public string $cuenta = '';

    /** De a cuántos comprobantes se traen (se leen de Odoo en vivo). */
    private const POR_TANDA = 35;

    public int $cantidad = self::POR_TANDA;

    public function alternarInfo(): void
    {
        $this->cuenta = $this->cuenta === 'info' ? '' : 'info';
        $this->cantidad = self::POR_TANDA;
    }

    public function verMas(): void
    {
        $this->cantidad += self::POR_TANDA;
    }

    public function render(CuentaRepository $cuenta, ClienteActivo $clienteActivo)
    {
        $cliente = $clienteActivo->actual();

        // El botón sólo existe si el cliente tiene algo facturado ahí.
        $tieneInfo = $cliente && $cuenta->tieneInfo();

        if (! $tieneInfo) {
            $this->cuenta = '';
        }

        $movimientos = $cuenta->movimientos($this->cuenta, $this->cantidad);

        return view('livewire.vistas.cuenta.estado-cuenta-page', [
            'cliente' => $cliente,
            'saldos' => $cuenta->saldos(),
            'movimientos' => $movimientos,
            // Si vino la tanda completa, seguramente haya más para traer.
            'hayMas' => count($movimientos) >= $this->cantidad,
            'tieneInfo' => $tieneInfo,
            'objetivos' => $cliente ? $cuenta->objetivos() : null,
            'motivo' => $cliente ? null : ($clienteActivo->puedeElegir()
                ? 'Elegí un cliente en Productos para ver su cuenta.'
                : 'Ingresá con tu usuario para ver el estado de tu cuenta.'),
        ]);
    }
}

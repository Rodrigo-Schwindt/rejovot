<?php

namespace App\Contracts;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Punto único por donde entra la información del catálogo.
 *
 * CatalogoLocal lee la copia sincronizada de Odoo; CatalogoDemo sigue existiendo
 * para lo que todavía no tiene datos reales y para las pruebas.
 */
interface CatalogoRepository
{
    /** Slides del banner de ofertas del encabezado. */
    public function ofertas(): array;

    /** Clientes disponibles para el selector superior. */
    public function clientes(): array;

    /** Opciones de los combos del buscador. */
    public function filtros(): array;

    /** Árbol de la búsqueda por vehículo: marca => modelos. */
    public function vehiculos(): array;

    /**
     * Listado paginado. Es la vía normal: el catálogo tiene 40k productos.
     *
     * @return LengthAwarePaginator<int, array>
     */
    public function paginados(array $filtros = [], int $porPagina = 20): LengthAwarePaginator;

    /** Listado sin paginar, acotado por $limite. Para usos internos. */
    public function productos(array $filtros = [], int $limite = 100): array;

    /** Ficha ampliada de un producto por código. */
    public function detalle(string $codigo): ?array;

    /** Alternativos y accesorios que Odoo le cargó al producto. */
    public function relacionados(string $codigo): array;
}

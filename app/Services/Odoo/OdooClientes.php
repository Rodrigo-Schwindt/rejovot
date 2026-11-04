<?php

namespace App\Services\Odoo;

/**
 * Lecturas de clientes y vendedores en Odoo.
 *
 * Se acota a la compañía de la web: la base tiene cinco y los partners de
 * Beneibrak no tienen que aparecer acá.
 */
class OdooClientes
{
    /** Lo que se lee de cada res.partner (ver Customer::guardarDesdeOdoo). */
    public const CAMPOS = [
        'id',
        'name',
        'vat',
        'email',
        'phone',
        'city',
        'user_id',
        'property_product_pricelist',
        'price_discount',
        'price_margin',
        'credit',
        'credit_limit',
        'active',
        'write_date',
    ];

    public function __construct(protected OdooClient $odoo)
    {
    }

    /** Usuarios internos: son los candidatos a vendedor. */
    public function vendedores(): array
    {
        return $this->odoo->searchRead('res.users', [
            ['share', '=', false],
        ], ['name', 'login', 'active'], [
            'order' => 'name asc',
            'context' => ['active_test' => false],
        ]);
    }

    /** Usuarios de portal: los clientes que ya entran al sistema. */
    public function usuariosPortal(): array
    {
        return $this->odoo->searchRead('res.users', [
            ['share', '=', true],
            ['active', '=', true],
        ], ['login', 'partner_id'], ['order' => 'id asc']);
    }

    public function clientesCount(?string $since): int
    {
        return (int) $this->odoo->call('res.partner', 'search_count', [$this->dominio($since)], [
            'context' => ['active_test' => false],
        ]);
    }

    public function clientesPage(?string $since, int $offset, int $limit = 500): array
    {
        return $this->odoo->searchRead('res.partner', $this->dominio($since), self::CAMPOS, [
            'offset' => $offset,
            'limit' => $limit,
            'order' => 'write_date asc, id asc',
            'context' => ['active_test' => false],
        ]);
    }

    /**
     * Un cliente puntual, aunque todavía no tenga ventas: el ingreso lo trae
     * en el momento si el sync aún no lo bajó.
     */
    public function cliente(int $partnerId): ?array
    {
        return $this->odoo->searchRead('res.partner', [
            ['id', '=', $partnerId],
            ...$this->dePropiaCompania(),
        ], self::CAMPOS, ['limit' => 1, 'context' => ['active_test' => false]])[0] ?? null;
    }

    /**
     * Clientes: los que tienen ventas (customer_rank) y también los que tienen
     * usuario de portal. Un cliente recién dado de alta en Odoo tiene
     * customer_rank 0 hasta su primera venta y si no, no podría entrar.
     */
    private function dominio(?string $since): array
    {
        $dominio = [
            '|', ['customer_rank', '>', 0], ['user_ids.share', '=', true],
            ...$this->dePropiaCompania(),
        ];

        if ($since) {
            $dominio[] = ['write_date', '>', $since];
        }

        return $dominio;
    }

    /** Sin compañía (compartidos) o de la compañía de la web. */
    private function dePropiaCompania(): array
    {
        return ['|', ['company_id', '=', false], ['company_id', '=', config('odoo.company_id')]];
    }
}

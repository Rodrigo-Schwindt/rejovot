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
        return $this->odoo->searchRead('res.partner', $this->dominio($since), [
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
        ], [
            'offset' => $offset,
            'limit' => $limit,
            'order' => 'write_date asc, id asc',
            'context' => ['active_test' => false],
        ]);
    }

    private function dominio(?string $since): array
    {
        $dominio = [
            ['customer_rank', '>', 0],
            '|', ['company_id', '=', false], ['company_id', '=', config('odoo.company_id')],
        ];

        if ($since) {
            $dominio[] = ['write_date', '>', $since];
        }

        return $dominio;
    }
}

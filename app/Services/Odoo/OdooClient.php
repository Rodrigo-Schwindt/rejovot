<?php

namespace App\Services\Odoo;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * Cliente JSON-RPC de Odoo. Es la única puerta de entrada al ERP:
 * el resto de la app habla con servicios que usan esta clase.
 */
class OdooClient
{
    protected ?int $uid = null;

    public function __construct(protected array $config)
    {
    }

    /** Login cacheado: devuelve el uid del usuario de servicio. */
    public function uid(): int
    {
        if ($this->uid !== null) {
            return $this->uid;
        }

        $this->uid = Cache::remember('odoo:uid', 3600, function () {
            $uid = $this->rpc('common', 'login', [
                $this->config['db'],
                $this->config['user'],
                $this->config['key'],
            ]);

            if (! is_int($uid) || $uid <= 0) {
                throw new OdooException('Credenciales de Odoo inválidas (revisá ODOO_DB, ODOO_USER y ODOO_KEY).');
            }

            return $uid;
        });

        return $this->uid;
    }

    /**
     * Valida las credenciales de otro usuario (cliente o vendedor) contra Odoo.
     * Devuelve su uid, o false si no son válidas. No toca la sesión de servicio.
     */
    public function autenticar(string $login, string $password): int|false
    {
        $uid = $this->rpc('common', 'login', [$this->config['db'], $login, $password]);

        return is_int($uid) && $uid > 0 ? $uid : false;
    }

    /** Versión del servidor. No necesita autenticación. */
    public function version(): array
    {
        return (array) $this->rpc('common', 'version', []);
    }

    public function call(string $model, string $method, array $args = [], array $kwargs = []): mixed
    {
        return $this->rpc('object', 'execute_kw', [
            $this->config['db'],
            $this->uid(),
            $this->config['key'],
            $model,
            $method,
            $args,
            (object) $kwargs,
        ]);
    }

    public function searchRead(string $model, array $domain, array $fields, array $options = []): array
    {
        return (array) $this->call($model, 'search_read', [$domain, $fields], $options);
    }

    public function searchCount(string $model, array $domain = []): int
    {
        return (int) $this->call($model, 'search_count', [$domain]);
    }

    public function read(string $model, array $ids, array $fields, array $context = []): array
    {
        return (array) $this->call($model, 'read', [$ids, $fields], $context ? ['context' => $context] : []);
    }

    public function create(string $model, array $values): int
    {
        return (int) $this->call($model, 'create', [$values]);
    }

    public function write(string $model, array $ids, array $values): bool
    {
        return (bool) $this->call($model, 'write', [$ids, $values]);
    }

    protected function rpc(string $service, string $method, array $args): mixed
    {
        if (blank($this->config['url']) || blank($this->config['db'])) {
            throw new OdooException('Falta configurar ODOO_URL y ODOO_DB en el .env.');
        }

        $response = Http::timeout($this->config['timeout'])
            ->acceptJson()
            ->post(rtrim($this->config['url'], '/') . '/jsonrpc', [
                'jsonrpc' => '2.0',
                'method' => 'call',
                'params' => compact('service', 'method', 'args'),
                'id' => uniqid(),
            ]);

        if ($response->failed()) {
            throw new OdooException("HTTP {$response->status()} desde Odoo");
        }

        $body = $response->json();

        if (isset($body['error'])) {
            throw new OdooException(
                $body['error']['data']['message']
                ?? $body['error']['message']
                ?? 'Error de Odoo'
            );
        }

        return $body['result'] ?? null;
    }
}

<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /** Roles del sitio: se crean solos cuando alguien entra con su usuario de Odoo. */
    public const VENDEDOR = 'vendedor';

    public const CLIENTE = 'cliente';

    /** Roles del panel administrativo: se cargan a mano desde Usuarios. */
    public const ROLES_PANEL = ['admin', 'user', 'viewer'];

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'odoo_uid',
        'customer_id',
        'salesperson_id',
        'last_login_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function salesperson(): BelongsTo
    {
        return $this->belongsTo(Salesperson::class);
    }

    /** Los que entran al panel: no se mezclan con los que vienen de Odoo. */
    public function scopeDelPanel(Builder $query): Builder
    {
        return $query->whereIn('role', self::ROLES_PANEL);
    }

    /** Los que entran al sitio con sus credenciales de Odoo. */
    public function scopeDelSitio(Builder $query): Builder
    {
        return $query->whereIn('role', [self::VENDEDOR, self::CLIENTE]);
    }

    public function esDelPanel(): bool
    {
        return in_array($this->role, self::ROLES_PANEL, true);
    }

    public function esVendedor(): bool
    {
        return $this->role === self::VENDEDOR;
    }

    public function esCliente(): bool
    {
        return $this->role === self::CLIENTE;
    }

    /** Puede operar en el sitio (catálogo, carrito, pedidos). */
    public function operaEnElSitio(): bool
    {
        return $this->esVendedor() || $this->esCliente();
    }
}

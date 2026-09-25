<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Artículo reclamado dentro de un reclamo. */
class ClaimItem extends Model
{
    protected $table = 'claim_items';

    protected $fillable = ['claim_id', 'codigo', 'nombre', 'cantidad', 'observacion'];

    protected function casts(): array
    {
        return ['cantidad' => 'integer'];
    }

    public function claim(): BelongsTo
    {
        return $this->belongsTo(Claim::class);
    }
}

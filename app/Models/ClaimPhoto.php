<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Foto adjunta a un reclamo. Vive en el disco privado. */
class ClaimPhoto extends Model
{
    protected $table = 'claim_photos';

    protected $fillable = ['claim_id', 'archivo', 'archivo_original'];

    public function claim(): BelongsTo
    {
        return $this->belongsTo(Claim::class);
    }
}

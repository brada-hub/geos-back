<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Mueble extends Model
{
    protected $table = 'muebles';

    protected $fillable = ['sede_id', 'nombre', 'filas', 'columnas'];

    public function sede(): BelongsTo
    {
        return $this->belongsTo(Sede::class);
    }

    public function cajones(): HasMany
    {
        return $this->hasMany(Cajon::class);
    }
}

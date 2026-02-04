<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Sede extends Model
{
    protected $table = 'sedes';

    protected $fillable = ['nombre'];

    public function muebles(): HasMany
    {
        return $this->hasMany(Mueble::class);
    }
}

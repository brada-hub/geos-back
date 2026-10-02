<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TipoContrato extends Model
{
    protected $table = 'tipos_contrato';

    protected $fillable = ['nombre', 'codigo', 'color'];

    public function empleados(): HasMany
    {
        return $this->hasMany(Empleado::class);
    }
}

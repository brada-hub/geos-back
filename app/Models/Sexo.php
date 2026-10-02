<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Sexo extends Model
{
    protected $table = 'sexos';

    protected $fillable = ['nombre', 'codigo'];

    public function empleados(): HasMany
    {
        return $this->hasMany(Empleado::class);
    }
}

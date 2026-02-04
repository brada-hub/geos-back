<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreEmpleadoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'numero_unico' => 'nullable|string|max:10',
            'codigo_archivo' => 'required|string|unique:empleados,codigo_archivo',
            'nombre_completo' => 'required|string|max:255',
            'cargo' => 'nullable|string|max:255',
            'cajon_id' => 'required|exists:cajones,id',
        ];
    }
}

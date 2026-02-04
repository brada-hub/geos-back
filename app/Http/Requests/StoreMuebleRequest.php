<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreMuebleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'sede_id' => 'required|exists:sedes,id',
            'nombre' => 'required|string|max:255',
            'filas' => 'required|integer|min:1|max:20',
            'columnas' => 'required|integer|min:1|max:20',
        ];
    }
}

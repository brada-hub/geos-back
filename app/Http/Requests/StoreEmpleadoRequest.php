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
            'codigo_archivo' => 'required|string|unique:empleados,codigo_archivo',
            'numero_unico' => 'nullable|string|max:20',
            'nombres' => 'required|string|max:150',
            'primer_apellido' => 'required|string|max:100',
            'segundo_apellido' => 'nullable|string|max:100',
            'documento_identidad' => 'required|string|max:30|unique:empleados,documento_identidad',
            'fecha_nacimiento' => 'nullable|date',
            'sexo_id' => 'required|exists:sexos,id',
            'sexo' => 'nullable|string|in:M,F',
            'tipo_contrato_id' => 'required|exists:tipos_contrato,id',
            'cargo_id' => 'nullable|exists:cargos,id',
            'cargo_nombre' => 'nullable|string|max:150',
            'sede_id' => 'required|exists:sedes,id',
            'cajon_id' => 'nullable|exists:cajones,id',
            'nombre_completo' => 'nullable|string|max:255',
        ];
    }
}

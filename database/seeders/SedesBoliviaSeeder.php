<?php

namespace Database\Seeders;

use App\Models\Cargo;
use App\Models\Empleado;
use App\Models\Movimiento;
use App\Models\Sede;
use App\Models\Sexo;
use App\Models\TipoContrato;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class SedesBoliviaSeeder extends Seeder
{
    public function run(): void
    {
        $sedes = [
            'COCHABAMBA',
            'IVIGARZAMA',
            'LA PAZ',
            'EL ALTO',
            'COBIJA',
            'GUAYARAMERIN',
            'SANTA CRUZ',
            'PUERTO QUIJARRO',
        ];

        foreach ($sedes as $nombreSede) {
            Sede::firstOrCreate(['nombre' => $nombreSede]);
        }

        // Crear/Asegurar registro de ejemplo de la captura
        // N°: 1 | ALONZO ROJAS LEIBI | CI: 4204219 | Exp: PD | Sexo: F | 22/01/1986 | Cargo: ADM CAMPUS | Sede: Cobija | Contrato: Indefinido
        $sedeCobija = Sede::where('nombre', 'like', '%COBIJA%')->first();
        $tipoIndefinido = TipoContrato::where('nombre', 'like', '%INDEFINIDO%')->first();
        $sexoFemenino = Sexo::where('codigo', 'F')->first();
        $cargoAdm = Cargo::firstOrCreate(['nombre' => 'ADM CAMPUS']);

        $ciEjemplo = '4204219 PD';
        $codigoArchivo = 'EXP-COB-4204219';

        $empleadoExiste = Empleado::where('documento_identidad', $ciEjemplo)
            ->orWhere('codigo_archivo', $codigoArchivo)
            ->first();

        if (!$empleadoExiste && $sedeCobija && $tipoIndefinido && $sexoFemenino) {
            $empleado = Empleado::create([
                'numero_unico' => '1',
                'codigo_archivo' => $codigoArchivo,
                'nombres' => 'LEIBI',
                'primer_apellido' => 'ALONZO',
                'segundo_apellido' => 'ROJAS',
                'documento_identidad' => $ciEjemplo,
                'fecha_nacimiento' => Carbon::createFromFormat('d/m/Y', '22/01/1986')->format('Y-m-d'),
                'sexo_id' => $sexoFemenino->id,
                'sexo' => 'F',
                'cargo_id' => $cargoAdm->id,
                'cargo' => $cargoAdm->nombre,
                'tipo_contrato_id' => $tipoIndefinido->id,
                'sede_id' => $sedeCobija->id,
                'cajon_id' => null, // Gaveta Virtual
                'estado' => 0,
            ]);

            Movimiento::create([
                'empleado_id' => $empleado->id,
                'tipo_movimiento' => 'general',
                'cajon_destino_id' => null,
                'tipo_contrato_nuevo_id' => $empleado->tipo_contrato_id,
                'cargo_nuevo' => $empleado->cargo,
                'comentario' => 'Importación inicial de personal desde planilla Excel',
            ]);
        }
    }
}

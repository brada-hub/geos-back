<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('empleados', function (Blueprint $table) {
            $table->string('nombres')->nullable()->after('nombre_completo');
            $table->string('primer_apellido')->nullable()->after('nombres');
            $table->string('segundo_apellido')->nullable()->after('primer_apellido');
            $table->string('documento_identidad')->nullable()->after('segundo_apellido');
            $table->date('fecha_nacimiento')->nullable()->after('documento_identidad');
            $table->string('sexo', 1)->nullable()->after('fecha_nacimiento'); // M / F
            $table->foreignId('cargo_id')->nullable()->after('cargo')->constrained('cargos')->onDelete('set null');
            $table->foreignId('tipo_contrato_id')->nullable()->after('cargo_id')->constrained('tipos_contrato')->onDelete('set null');
        });

        // Asegurar que existan los 3 tipos de contrato base
        $now = now();
        DB::table('tipos_contrato')->insertOrIgnore([
            ['id' => 1, 'nombre' => 'PRESTACIÓN DE SERVICIOS', 'codigo' => 'SERVICIOS', 'color' => 'purple-9', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 2, 'nombre' => 'PLAZO FIJO', 'codigo' => 'PLAZO_FIJO', 'color' => 'teal-8', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 3, 'nombre' => 'INDEFINIDO', 'codigo' => 'INDEFINIDO', 'color' => 'indigo-9', 'created_at' => $now, 'updated_at' => $now],
        ]);

        // Migrar registros existentes en empleados
        $empleados = DB::table('empleados')->get();
        foreach ($empleados as $emp) {
            $parts = array_values(array_filter(explode(' ', trim($emp->nombre_completo ?? ''))));
            $nombres = 'Sin Nombre';
            $pApellido = 'Sin Apellido';
            $sApellido = null;

            if (count($parts) === 1) {
                $nombres = $parts[0];
                $pApellido = '-';
            } elseif (count($parts) === 2) {
                $nombres = $parts[0];
                $pApellido = $parts[1];
            } elseif (count($parts) === 3) {
                $nombres = $parts[0];
                $pApellido = $parts[1];
                $sApellido = $parts[2];
            } elseif (count($parts) >= 4) {
                $nombres = $parts[0] . ' ' . $parts[1];
                $pApellido = $parts[2];
                $sApellido = implode(' ', array_slice($parts, 3));
            }

            // Normalizar cargo si existe
            $cargoId = null;
            if (!empty($emp->cargo)) {
                $cargoNombre = trim($emp->cargo);
                $existingCargo = DB::table('cargos')->where('nombre', $cargoNombre)->first();
                if ($existingCargo) {
                    $cargoId = $existingCargo->id;
                } else {
                    $cargoId = DB::table('cargos')->insertGetId([
                        'nombre' => $cargoNombre,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }
            }

            $doc = $emp->numero_unico ? 'CI-' . $emp->numero_unico : 'DOC-' . $emp->id;

            DB::table('empleados')->where('id', $emp->id)->update([
                'nombres' => $nombres,
                'primer_apellido' => $pApellido,
                'segundo_apellido' => $sApellido,
                'documento_identidad' => $doc,
                'sexo' => 'M',
                'cargo_id' => $cargoId,
                'tipo_contrato_id' => 3, // Por defecto Indefinido para registros existentes
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('empleados', function (Blueprint $table) {
            $table->dropForeign(['tipo_contrato_id']);
            $table->dropForeign(['cargo_id']);
            $table->dropColumn([
                'nombres',
                'primer_apellido',
                'segundo_apellido',
                'documento_identidad',
                'fecha_nacimiento',
                'sexo',
                'cargo_id',
                'tipo_contrato_id',
            ]);
        });
    }
};

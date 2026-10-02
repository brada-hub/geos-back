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
            $table->foreignId('sede_id')->nullable()->after('tipo_contrato_id')->constrained('sedes')->onDelete('set null');
        });

        // Asegurar que exista al menos una sede base
        $primeraSede = DB::table('sedes')->first();
        if (!$primeraSede) {
            $sedeId = DB::table('sedes')->insertGetId([
                'nombre' => 'SEDE CENTRAL',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } else {
            $sedeId = $primeraSede->id;
        }

        // Backfill sede_id para los empleados existentes basándose en su cajón o por defecto
        $empleados = DB::table('empleados')->get();
        foreach ($empleados as $emp) {
            $targetSedeId = null;
            if ($emp->cajon_id) {
                $cajon = DB::table('cajones')->where('id', $emp->cajon_id)->first();
                if ($cajon) {
                    $mueble = DB::table('muebles')->where('id', $cajon->mueble_id)->first();
                    if ($mueble && $mueble->sede_id) {
                        $targetSedeId = $mueble->sede_id;
                    }
                }
            }
            if (!$targetSedeId) {
                $targetSedeId = $sedeId;
            }

            DB::table('empleados')->where('id', $emp->id)->update([
                'sede_id' => $targetSedeId,
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('empleados', function (Blueprint $table) {
            $table->dropForeign(['sede_id']);
            $table->dropColumn('sede_id');
        });
    }
};

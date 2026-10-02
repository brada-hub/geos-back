<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('movimientos', function (Blueprint $table) {
            $table->string('tipo_movimiento', 50)->default('ubicacion')->after('empleado_id');
            $table->foreignId('tipo_contrato_anterior_id')->nullable()->after('cajon_destino_id')->constrained('tipos_contrato')->onDelete('set null');
            $table->foreignId('tipo_contrato_nuevo_id')->nullable()->after('tipo_contrato_anterior_id')->constrained('tipos_contrato')->onDelete('set null');
            $table->string('cargo_anterior')->nullable()->after('tipo_contrato_nuevo_id');
            $table->string('cargo_nuevo')->nullable()->after('cargo_anterior');
            $table->foreignId('sede_origen_id')->nullable()->after('cargo_nuevo')->constrained('sedes')->onDelete('set null');
            $table->foreignId('sede_destino_id')->nullable()->after('sede_origen_id')->constrained('sedes')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('movimientos', function (Blueprint $table) {
            $table->dropForeign(['tipo_contrato_anterior_id']);
            $table->dropForeign(['tipo_contrato_nuevo_id']);
            $table->dropForeign(['sede_origen_id']);
            $table->dropForeign(['sede_destino_id']);
            $table->dropColumn([
                'tipo_movimiento',
                'tipo_contrato_anterior_id',
                'tipo_contrato_nuevo_id',
                'cargo_anterior',
                'cargo_nuevo',
                'sede_origen_id',
                'sede_destino_id',
            ]);
        });
    }
};

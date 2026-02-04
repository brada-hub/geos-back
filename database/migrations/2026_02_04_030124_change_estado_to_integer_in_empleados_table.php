<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // First, drop the enum column and recreate as integer
        Schema::table('empleados', function (Blueprint $table) {
            $table->dropColumn('estado');
        });

        Schema::table('empleados', function (Blueprint $table) {
            // 0 = presente, 1 = ausente, 2 = prestado
            $table->tinyInteger('estado')->default(0)->after('cajon_id');
        });

        // Also update movimientos table
        Schema::table('movimientos', function (Blueprint $table) {
            $table->dropColumn(['estado_anterior', 'estado_nuevo']);
        });

        Schema::table('movimientos', function (Blueprint $table) {
            $table->tinyInteger('estado_anterior')->nullable()->after('cajon_destino_id');
            $table->tinyInteger('estado_nuevo')->nullable()->after('estado_anterior');
        });
    }

    public function down(): void
    {
        Schema::table('empleados', function (Blueprint $table) {
            $table->dropColumn('estado');
        });

        Schema::table('empleados', function (Blueprint $table) {
            $table->enum('estado', ['presente', 'ausente', 'prestado'])->default('presente')->after('cajon_id');
        });

        Schema::table('movimientos', function (Blueprint $table) {
            $table->dropColumn(['estado_anterior', 'estado_nuevo']);
        });

        Schema::table('movimientos', function (Blueprint $table) {
            $table->string('estado_anterior')->nullable();
            $table->string('estado_nuevo')->nullable();
        });
    }
};

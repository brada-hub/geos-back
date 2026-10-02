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
        Schema::create('sexos', function (Blueprint $table) {
            $table->id();
            $table->string('nombre')->unique(); // MASCULINO, FEMENINO
            $table->string('codigo', 5)->unique(); // M, F
            $table->timestamps();
        });

        $now = now();
        DB::table('sexos')->insert([
            ['id' => 1, 'nombre' => 'MASCULINO', 'codigo' => 'M', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 2, 'nombre' => 'FEMENINO', 'codigo' => 'F', 'created_at' => $now, 'updated_at' => $now],
        ]);

        Schema::table('empleados', function (Blueprint $table) {
            $table->foreignId('sexo_id')->nullable()->after('fecha_nacimiento')->constrained('sexos')->onDelete('set null');
        });

        // Migrar datos existentes de empleados
        DB::table('empleados')->where('sexo', 'M')->orWhereNull('sexo')->update(['sexo_id' => 1]);
        DB::table('empleados')->where('sexo', 'F')->update(['sexo_id' => 2]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('empleados', function (Blueprint $table) {
            $table->dropForeign(['sexo_id']);
            $table->dropColumn('sexo_id');
        });

        Schema::dropIfExists('sexos');
    }
};

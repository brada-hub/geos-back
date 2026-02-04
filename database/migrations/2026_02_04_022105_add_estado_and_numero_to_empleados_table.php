<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('empleados', function (Blueprint $table) {
            $table->string('numero_unico', 10)->nullable()->after('id');
            $table->enum('estado', ['presente', 'ausente', 'prestado'])->default('presente')->after('cajon_id');
        });
    }

    public function down(): void
    {
        Schema::table('empleados', function (Blueprint $table) {
            $table->dropColumn(['numero_unico', 'estado']);
        });
    }
};

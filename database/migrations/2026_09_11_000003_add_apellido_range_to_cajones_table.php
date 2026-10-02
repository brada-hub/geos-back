<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cajones', function (Blueprint $table) {
            $table->string('apellido_desde', 50)->nullable()->after('etiqueta');
            $table->string('apellido_hasta', 50)->nullable()->after('apellido_desde');
        });
    }

    public function down(): void
    {
        Schema::table('cajones', function (Blueprint $table) {
            $table->dropColumn(['apellido_desde', 'apellido_hasta']);
        });
    }
};

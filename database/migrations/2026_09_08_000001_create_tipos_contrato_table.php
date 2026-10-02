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
        Schema::create('tipos_contrato', function (Blueprint $table) {
            $table->id();
            $table->string('nombre')->unique(); // PRESTACIÓN DE SERVICIOS, PLAZO FIJO, INDEFINIDO
            $table->string('codigo')->unique(); // SERVICIOS, PLAZO_FIJO, INDEFINIDO
            $table->string('color')->default('primary'); // Color Quasar: deep-purple, teal, indigo
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tipos_contrato');
    }
};

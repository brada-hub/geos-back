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
        Schema::create('cajones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mueble_id')->constrained()->onDelete('cascade');
            $table->integer('fila');
            $table->integer('columna');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cajones');
    }
};

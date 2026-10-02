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
        Schema::create('cajon_tipo_contrato', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cajon_id')->constrained('cajones')->onDelete('cascade');
            $table->foreignId('tipo_contrato_id')->constrained('tipos_contrato')->onDelete('cascade');
            $table->timestamps();

            $table->unique(['cajon_id', 'tipo_contrato_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cajon_tipo_contrato');
    }
};

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
        Schema::table('cajones', function (Blueprint $table) {
            $table->string('etiqueta')->nullable()->after('columna');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cajones', function (Blueprint $table) {
            $table->dropColumn('etiqueta');
        });
    }
};

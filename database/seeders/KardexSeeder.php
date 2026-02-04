<?php

namespace Database\Seeders;

use App\Models\Cajon;
use App\Models\Empleado;
use App\Models\Mueble;
use App\Models\Sede;
use Illuminate\Database\Seeder;

class KardexSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $sede = Sede::create(['nombre' => 'Sede Central RRHH']);

        $mueblesConfig = [
            ['nombre' => 'Mueble A (Principal)', 'filas' => 4, 'columnas' => 5],
            ['nombre' => 'Mueble B (Torre)', 'filas' => 4, 'columnas' => 1],
            ['nombre' => 'Mueble C (Torre)', 'filas' => 4, 'columnas' => 1],
            ['nombre' => 'Mueble D (Mediano)', 'filas' => 4, 'columnas' => 3],
        ];

        foreach ($mueblesConfig as $config) {
            $mueble = Mueble::create([
                'sede_id' => $sede->id,
                'nombre' => $config['nombre'],
                'filas' => $config['filas'],
                'columnas' => $config['columnas'],
            ]);

            // Crear cajones dinámicamente
            for ($f = 1; $f <= $config['filas']; $f++) {
                for ($c = 1; $c <= $config['columnas']; $c++) {
                    Cajon::create([
                        'mueble_id' => $mueble->id,
                        'fila' => $f,
                        'columna' => $c,
                    ]);
                }
            }
        }

        // Crear algunos empleados de prueba
        $cajones = Cajon::all();
        $nombres = [
            'Juan Pérez', 'María García', 'Carlos Rodríguez', 'Ana Martínez',
            'Luis López', 'Elena Sánchez', 'Pedro Ramírez', 'Lucía Torres',
            'Diego Morales', 'Sofía Castro'
        ];

        foreach ($nombres as $i => $nombre) {
            Empleado::create([
                'nombre_completo' => $nombre,
                'cargo' => 'Analista ' . ($i + 1),
                'cajon_id' => $cajones->random()->id,
            ]);
        }
    }
}

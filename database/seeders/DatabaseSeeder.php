<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Usuario Administrador por Defecto
        $adminEmail = env('ADMIN_EMAIL', 'admin@docus.com');
        $adminPass = env('ADMIN_PASSWORD', 'Admin123*');

        User::firstOrCreate(
            ['email' => $adminEmail],
            [
                'name' => 'Administrador RRHH',
                'password' => Hash::make($adminPass),
                'email_verified_at' => now(),
            ]
        );

        $this->call(SedesBoliviaSeeder::class);
    }
}

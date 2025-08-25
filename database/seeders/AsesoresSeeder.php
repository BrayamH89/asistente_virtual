<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Area;
use Illuminate\Support\Facades\Hash;

class AsesoresSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Crear áreas
        $areas = [
            'Atención Académica',
            'Soporte Técnico',
            'Gestión de Pagos',
            'Orientación General'
        ];

        foreach ($areas as $nombre) {
            Area::firstOrCreate(['nombre' => $nombre]);
        }

        // Crear asesores
        $asesores = [
            [
                'name' => 'Juan Pérez',
                'email' => 'juan.asesor@example.com',
                'password' => Hash::make('password123'),
                'area_id' => Area::where('nombre', 'Atención Académica')->first()->id,
            ],
            [
                'name' => 'María López',
                'email' => 'maria.asesor@example.com',
                'password' => Hash::make('password123'),
                'area_id' => Area::where('nombre', 'Soporte Técnico')->first()->id,
            ],
            [
                'name' => 'Carlos Gómez',
                'email' => 'carlos.asesor@example.com',
                'password' => Hash::make('password123'),
                'area_id' => Area::where('nombre', 'Gestión de Pagos')->first()->id,
            ],
        ];

        foreach ($asesores as $asesor) {
            User::updateOrCreate(
                ['email' => $asesor['email']],
                [
                    'name' => $asesor['name'],
                    'password' => $asesor['password'],
                    'role_id' => 2, // Suponiendo que el rol "asesor" es ID 2
                    'area_id' => $asesor['area_id'],
                ]
            );
        }
    }
}

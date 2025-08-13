<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Role;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        Role::create(['name' => 'admin', 'description' => 'Administrador del sistema']);
        Role::create(['name' => 'advisor', 'description' => 'Asesor de usuarios']);
        Role::create(['name' => 'user', 'description' => 'Usuario estándar']);
    }
}

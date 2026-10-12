<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class AdminUserSeeder extends Seeder
{
    /**
     * Buat akun admin tunggal.
     */
    public function run(): void
    {
        User::query()->updateOrCreate(
            ['email' => 'admin@kolamprima.test'],
            [
                'name' => 'Admin Kolam Prima',
                'password' => 'password',
            ],
        );
    }
}

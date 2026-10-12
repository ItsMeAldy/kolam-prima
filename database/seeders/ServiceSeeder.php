<?php

namespace Database\Seeders;

use App\Models\Service;
use Illuminate\Database\Seeder;

class ServiceSeeder extends Seeder
{
    /**
     * Isi tiga kartu layanan utama.
     */
    public function run(): void
    {
        $services = [
            [
                'title' => 'Desain & Gambar',
                'description' => 'Pembuatan gambar 2D dan 3D, rencana anggaran biaya, serta detail teknis konstruksi kolam renang Anda.',
                'icon' => 'pencil-ruler',
                'wa_message' => 'Halo Kolam Prima, saya tertarik dengan layanan Desain & Gambar kolam renang.',
                'sort_order' => 1,
            ],
            [
                'title' => 'Pembangunan Kolam',
                'description' => 'Pelaksanaan konstruksi dari awal, mulai dari struktur, sistem sirkulasi air, hingga finishing yang rapi.',
                'icon' => 'hammer',
                'wa_message' => 'Halo Kolam Prima, saya tertarik dengan layanan Pembangunan Kolam renang.',
                'sort_order' => 2,
            ],
            [
                'title' => 'Perawatan Kolam',
                'description' => 'Perawatan rutin bulanan, penanganan air keruh, perbaikan kebocoran, dan servis berkala kolam renang.',
                'icon' => 'droplets',
                'wa_message' => 'Halo Kolam Prima, saya tertarik dengan layanan Perawatan Kolam renang.',
                'sort_order' => 3,
            ],
        ];

        foreach ($services as $service) {
            Service::query()->updateOrCreate(
                ['title' => $service['title']],
                $service,
            );
        }
    }
}

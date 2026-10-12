<?php

namespace Database\Seeders;

use App\Models\SiteSetting;
use Illuminate\Database\Seeder;

class SiteSettingSeeder extends Seeder
{
    /**
     * Isi pengaturan situs dengan data placeholder.
     */
    public function run(): void
    {
        SiteSetting::query()->updateOrCreate(['id' => 1], [
            'brand_name' => 'Kolam Prima',
            'tagline' => 'Desain, Bangun & Rawat Kolam Renang Impian Anda',
            'whatsapp_number' => '6281234567890',
            'email' => 'halo@kolamprima.test',
            'address' => 'Jl. Contoh Raya No. 123, Jakarta Selatan, DKI Jakarta 12345',
            'instagram_url' => 'https://instagram.com/kolamprima',
            'tiktok_url' => 'https://tiktok.com/@kolamprima',
            'hero_headline' => 'Wujudkan Kolam Renang Impian Anda',
            'hero_subheadline' => 'Layanan lengkap mulai dari desain, pembangunan, hingga perawatan berkala untuk rumah, villa, dan hotel.',
            'stat_1_label' => 'Proyek Selesai',
            'stat_1_value' => '150+',
            'stat_2_label' => 'Tahun Pengalaman',
            'stat_2_value' => '12',
            'stat_3_label' => 'Klien Puas',
            'stat_3_value' => '120+',
            'stat_4_label' => 'Kota Layanan',
            'stat_4_value' => '8',
        ]);
    }
}

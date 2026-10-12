<?php

namespace Database\Seeders;

use App\Models\Package;
use Illuminate\Database\Seeder;

class PackageSeeder extends Seeder
{
    /**
     * Isi tiga paket layanan.
     */
    public function run(): void
    {
        $packages = [
            [
                'name' => 'Desain Only',
                'tagline' => 'Cocok untuk Anda yang sudah punya kontraktor sendiri.',
                'features' => [
                    'Gambar desain 2D & 3D',
                    'Rencana Anggaran Biaya (RAB)',
                    'Detail teknis konstruksi',
                    'Konsultasi desain',
                ],
                'price' => null,
                'is_popular' => false,
                'sort_order' => 1,
            ],
            [
                'name' => 'Desain + Bangun',
                'tagline' => 'Paket lengkap dari desain sampai kolam siap pakai.',
                'features' => [
                    'Semua fitur paket Desain Only',
                    'Pelaksanaan konstruksi penuh',
                    'Sistem sirkulasi & filtrasi air',
                    'Finishing dan pembersihan akhir',
                    'Garansi konstruksi',
                ],
                'price' => null,
                'is_popular' => true,
                'sort_order' => 2,
            ],
            [
                'name' => 'Maintenance Bulanan',
                'tagline' => 'Jaga kolam tetap jernih sepanjang tahun.',
                'features' => [
                    'Kunjungan perawatan rutin',
                    'Pembersihan & penyaringan air',
                    'Pengecekan kualitas air',
                    'Perbaikan ringan',
                ],
                'price' => null,
                'is_popular' => false,
                'sort_order' => 3,
            ],
        ];

        foreach ($packages as $package) {
            Package::query()->updateOrCreate(
                ['name' => $package['name']],
                $package,
            );
        }
    }
}

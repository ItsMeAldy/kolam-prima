<?php

namespace Database\Seeders;

use App\Models\Project;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProjectSeeder extends Seeder
{
    /**
     * Isi portofolio dengan proyek nyata beserta gambarnya.
     *
     * Data judul/lokasi bersifat placeholder — perbarui lewat panel admin.
     * Berkas gambar disalin manual ke storage/app/public/media/projects.
     */
    public function run(): void
    {
        $this->removeLegacyPlaceholders();

        $disk = Storage::disk('public');

        $projects = [
            [
                'title' => 'Pembangunan Kolam Renang Rumah Pribadi',
                'location' => 'Jabodetabek',
                'property_type' => 'rumah',
                'description' => 'Proses konstruksi kolam renang mulai dari struktur beton, sistem sirkulasi air, hingga finishing.',
                'images' => ['konstruksi-01.jpeg', 'konstruksi-02.jpeg', 'konstruksi-03.jpeg'],
            ],
            [
                'title' => 'Desain 3D Kolam Renang Villa',
                'location' => 'Bandung',
                'property_type' => 'villa',
                'description' => 'Visualisasi desain 3D kolam renang dengan air terjun, deck kayu, dan taman tropis sebelum pelaksanaan.',
                'images' => ['desain-3d-01.jpeg', 'desain-3d-02.jpeg', 'desain-3d-03.jpeg', 'desain-3d-04.jpeg'],
            ],
            [
                'title' => 'Kolam Renang Villa Tepi Pantai',
                'location' => 'Pandeglang',
                'property_type' => 'villa',
                'description' => 'Kolam renang privat menghadap laut dengan mozaik biru, jaring gantung, dan area bersantai.',
                'images' => ['villa-pantai-01.jpeg', 'villa-pantai-02.jpeg', 'villa-pantai-03.jpeg'],
            ],
            [
                'title' => 'Kolam Renang Rumah Gaya Klasik',
                'location' => 'Bogor',
                'property_type' => 'rumah',
                'description' => 'Kolam renang ukuran besar dengan deck komposit, dinding batu alam, dan pencahayaan taman.',
                'images' => ['rumah-klasik-01.jpeg', 'rumah-klasik-02.jpeg', 'rumah-klasik-03.jpeg'],
            ],
            [
                'title' => 'Kolam Renang Rumah Modern',
                'location' => 'Tangerang',
                'property_type' => 'rumah',
                'description' => 'Kolam renang minimalis dengan roster dinding, pencahayaan bawah air, dan area kering yang rapi.',
                'images' => ['rumah-modern-01.jpeg', 'rumah-modern-02.jpeg'],
            ],
        ];

        foreach ($projects as $index => $data) {
            $project = Project::query()->updateOrCreate(
                ['slug' => Str::slug($data['title'])],
                [
                    'title' => $data['title'],
                    'location' => $data['location'],
                    'property_type' => $data['property_type'],
                    'description' => $data['description'],
                    'sort_order' => $index + 1,
                    'is_published' => true,
                ],
            );

            foreach ($data['images'] as $imageIndex => $filename) {
                $path = "media/projects/{$filename}";

                // Lewati berkas yang belum disalin (mis. saat test).
                if (! $disk->exists($path)) {
                    continue;
                }

                $project->images()->updateOrCreate(
                    ['path' => $path],
                    ['image_url' => null, 'sort_order' => $imageIndex + 1],
                );
            }
        }
    }

    /**
     * Hapus proyek placeholder lama (gambar picsum) agar tidak tampil.
     */
    private function removeLegacyPlaceholders(): void
    {
        $legacySlugs = [
            'kolam-renang-villa-bukit-hijau',
            'kolam-renang-rumah-pak-andi',
            'kolam-renang-hotel-nusa-indah',
            'kolam-renang-villa-pantai',
            'kolam-renang-rumah-minimalis',
            'kolam-renang-resort-ubud',
            'kolam-renang-villa-modern',
            'kolam-renang-rumah-klasik',
            'kolam-renang-hotel-grand',
        ];

        Project::query()->whereIn('slug', $legacySlugs)->delete();
    }
}

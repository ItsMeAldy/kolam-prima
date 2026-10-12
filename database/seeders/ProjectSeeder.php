<?php

namespace Database\Seeders;

use App\Models\Project;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ProjectSeeder extends Seeder
{
    /**
     * Isi sembilan proyek portofolio beserta gambarnya.
     */
    public function run(): void
    {
        $projects = [
            ['title' => 'Kolam Renang Villa Bukit Hijau', 'location' => 'Bandung', 'property_type' => 'villa', 'description' => 'Kolam infinity dengan pemandangan lembah, sistem sirkulasi otomatis.'],
            ['title' => 'Kolam Renang Rumah Pak Andi', 'location' => 'Jakarta Selatan', 'property_type' => 'rumah', 'description' => 'Kolam keluarga ukuran 3x8 meter dengan area anak dan lampu LED.'],
            ['title' => 'Kolam Renang Hotel Nusa Indah', 'location' => 'Bali', 'property_type' => 'hotel', 'description' => 'Kolam utama hotel 12x25 meter dengan sistem filtrasi efisien.'],
            ['title' => 'Kolam Renang Villa Pantai', 'location' => 'Bogor', 'property_type' => 'villa', 'description' => 'Kolam privat dengan deck kayu dan air terjun dekoratif.'],
            ['title' => 'Kolam Renang Rumah Minimalis', 'location' => 'Tangerang', 'property_type' => 'rumah', 'description' => 'Desain minimalis dengan garis tegas dan pencahayaan tersembunyi.'],
            ['title' => 'Kolam Renang Resort Ubud', 'location' => 'Ubud', 'property_type' => 'hotel', 'description' => 'Kolam tepi tebing dengan nuansa tropis dan batu alam.'],
            ['title' => 'Kolam Renang Villa Modern', 'location' => 'Surabaya', 'property_type' => 'villa', 'description' => 'Kolam dengan sistem salt water dan otomatisasi pencahayaan.'],
            ['title' => 'Kolam Renang Rumah Klasik', 'location' => 'Yogyakarta', 'property_type' => 'rumah', 'description' => 'Kolam bergaya klasik dengan ornamen batu dan air mancur.'],
            ['title' => 'Kolam Renang Hotel Grand', 'location' => 'Semarang', 'property_type' => 'hotel', 'description' => 'Dua kolam terpisah untuk dewasa dan anak dengan area lounge.'],
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

            // Tiga gambar placeholder per proyek.
            foreach (range(1, 3) as $imageIndex) {
                $seed = ($index * 3) + $imageIndex;
                $project->images()->updateOrCreate(
                    ['image_url' => "https://picsum.photos/seed/kolam-{$seed}/1200/800"],
                    ['sort_order' => $imageIndex],
                );
            }
        }
    }
}

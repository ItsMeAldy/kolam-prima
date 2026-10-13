<?php

namespace Database\Seeders;

use App\Models\Video;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

class VideoSeeder extends Seeder
{
    /**
     * Isi galeri video dari berkas di storage/app/public/media.
     *
     * Poster dibuat dari frame video (ffmpeg). Perbarui judul lewat panel admin.
     */
    public function run(): void
    {
        $disk = Storage::disk('public');

        $videos = [
            ['title' => 'Proses Pembangunan Kolam Renang', 'video' => 'video-01.mp4', 'thumb' => 'video-01.jpg'],
            ['title' => 'Detail Finishing Kolam', 'video' => 'video-02.mp4', 'thumb' => 'video-02.jpg'],
            ['title' => 'Kolam Renang Villa Tepi Pantai', 'video' => 'video-03.mp4', 'thumb' => 'video-03.jpg'],
            ['title' => 'Kolam Renang Rumah Modern', 'video' => 'video-04.mp4', 'thumb' => 'video-04.jpg'],
            ['title' => 'Hasil Akhir Pengerjaan', 'video' => 'video-05.mp4', 'thumb' => 'video-05.jpg'],
        ];

        foreach ($videos as $index => $data) {
            $videoPath = "media/videos/{$data['video']}";

            // Lewati berkas yang belum disalin (mis. saat test).
            if (! $disk->exists($videoPath)) {
                continue;
            }

            $thumbnailPath = "media/thumbs/{$data['thumb']}";

            Video::query()->updateOrCreate(
                ['video_path' => $videoPath],
                [
                    'title' => $data['title'],
                    'thumbnail_path' => $disk->exists($thumbnailPath) ? $thumbnailPath : null,
                    'sort_order' => $index + 1,
                    'is_published' => true,
                ],
            );
        }
    }
}

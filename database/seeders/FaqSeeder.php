<?php

namespace Database\Seeders;

use App\Models\Faq;
use Illuminate\Database\Seeder;

class FaqSeeder extends Seeder
{
    /**
     * Isi enam pertanyaan yang sering diajukan klien.
     */
    public function run(): void
    {
        $faqs = [
            [
                'question' => 'Berapa estimasi biaya pembuatan kolam renang?',
                'answer' => 'Biaya tergantung ukuran, kedalaman, jenis finishing, dan sistem sirkulasi yang dipilih. Setelah survei lokasi dan diskusi kebutuhan, kami kirimkan rencana anggaran biaya (RAB) terperinci tanpa biaya awal. Kisaran umum proyek rumah tinggal ada di daftar paket pada halaman ini.',
                'sort_order' => 1,
            ],
            [
                'question' => 'Berapa lama waktu pengerjaan sampai kolam bisa dipakai?',
                'answer' => 'Kolam rumah tinggal berukuran standar umumnya selesai dalam 3–6 minggu, dihitung setelah desain disetujui dan material tiba di lokasi. Kolam komersial atau bentuk khusus bisa lebih lama. Kami berikan jadwal tahapan pekerjaan di awal agar Anda bisa memantau progres.',
                'sort_order' => 2,
            ],
            [
                'question' => 'Apakah ada garansi untuk hasil pekerjaan?',
                'answer' => 'Ada. Kami memberikan garansi konstruksi dan kebocoran untuk periode yang disepakati dalam kontrak, serta pendampingan teknis setelah serah terima. Garansi berlaku selama pekerjaan tidak dialihkan ke pihak lain dan perawatan dasar dilakukan sesuai anjuran.',
                'sort_order' => 3,
            ],
            [
                'question' => 'Sistem sirkulasi air seperti apa yang dipakai?',
                'answer' => 'Kami memakai sistem sirkulasi tertutup (skimmer atau overflow) dengan pompa dan filter pasir, ditambah sistem pemipaan yang dirancang sesuai bentuk kolam. Untuk kebutuhan tertentu bisa ditambah sistem pemanas, pencahayaan LED, atau pompa hemat energi. Semua komponen memakai merek yang mudah dicari di pasaran.',
                'sort_order' => 4,
            ],
            [
                'question' => 'Apakah termasuk perawatan berkala setelah kolam jadi?',
                'answer' => 'Paket perawatan bulanan tersedia terpisah, mencakup pengecekan kualitas air, pembersihan dasar dan dinding kolam, serta servis pompa dan filter. Untuk Anda yang ingin mengurus sendiri, kami sediakan panduan singkat dan bisa dihubungi saat ada kendala.',
                'sort_order' => 5,
            ],
            [
                'question' => 'Saya hanya butuh desain, tanpa pembangunan. Bisa?',
                'answer' => 'Bisa. Layanan desain dan gambar berdiri sendiri: gambar 2D dan 3D, detail teknis, serta rencana anggaran biaya yang bisa Anda bawa ke kontraktor pilihan sendiri. Banyak klien memakai layanan ini untuk menyiapkan dokumen sebelum pembangunan dimulai.',
                'sort_order' => 6,
            ],
        ];

        foreach ($faqs as $faq) {
            Faq::query()->updateOrCreate(
                ['question' => $faq['question']],
                [...$faq, 'is_published' => true],
            );
        }
    }
}

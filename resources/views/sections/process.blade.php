@php
    $steps = [
        ['title' => 'Konsultasi Awal', 'desc' => 'Diskusi kebutuhan, anggaran, dan konsep kolam renang yang Anda inginkan.'],
        ['title' => 'Survei Lokasi', 'desc' => 'Tim kami mengukur dan menilai kondisi lahan untuk perencanaan yang akurat.'],
        ['title' => 'Desain & Rencana Anggaran', 'desc' => 'Pembuatan gambar desain beserta rincian biaya yang transparan.'],
        ['title' => 'DP & Pelaksanaan Konstruksi', 'desc' => 'Pengerjaan konstruksi oleh tim berpengalaman sesuai jadwal.'],
        ['title' => 'Serah Terima Proyek', 'desc' => 'Kolam siap digunakan, lengkap dengan panduan perawatan.'],
        ['title' => 'Perawatan Berkala', 'desc' => 'Layanan perawatan opsional agar kolam selalu jernih dan awet.'],
    ];
@endphp

<section id="proses" class="py-16 sm:py-24">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <x-section-heading
            eyebrow="Proses Kerja"
            title="Alur Kerja yang Jelas & Aman"
            subtitle="Setiap tahap transparan, sehingga Anda tahu persis perkembangan proyek."
        />

        <div class="mt-14 grid gap-6 md:grid-cols-2 lg:grid-cols-3">
            @foreach ($steps as $index => $step)
                <div class="relative rounded-2xl border border-slate-100 bg-white p-7 shadow-sm">
                    <span class="grid h-12 w-12 place-items-center rounded-xl bg-navy-900 font-heading text-lg font-bold text-white">
                        {{ $index + 1 }}
                    </span>
                    <h3 class="mt-5 font-heading text-lg font-bold text-navy-900">{{ $step['title'] }}</h3>
                    <p class="mt-2 text-sm leading-relaxed text-navy-800/60">{{ $step['desc'] }}</p>
                </div>
            @endforeach
        </div>
    </div>
</section>

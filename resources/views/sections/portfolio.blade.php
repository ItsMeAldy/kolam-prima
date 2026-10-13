<section id="portofolio" class="bg-muted py-16 sm:py-24">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <x-section-heading
            eyebrow="Portofolio"
            title="Proyek yang Sudah Kami Kerjakan"
            subtitle="Bukti nyata dari berbagai jenis properti di seluruh Indonesia."
        />

        <div class="mt-14 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
            @forelse ($projects as $project)
                <article class="group overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-900/5 transition duration-300 hover:-translate-y-1 hover:shadow-xl hover:shadow-primary-900/5">
                    <div class="relative aspect-[4/3] overflow-hidden">
                        <img src="{{ $project->coverUrl() }}"
                             alt="{{ $project->title }}"
                             loading="lazy"
                             class="h-full w-full object-cover transition duration-500 group-hover:scale-105">
                        <span class="absolute left-4 top-4 rounded-full bg-white/90 px-3 py-1 text-xs font-semibold text-primary-700 backdrop-blur">
                            {{ $project->propertyTypeLabel() }}
                        </span>
                    </div>
                    <div class="p-5">
                        <h3 class="font-heading text-lg font-bold text-navy-900">{{ $project->title }}</h3>
                        <p class="mt-1 flex items-center gap-1.5 text-sm text-navy-800/60">
                            <svg class="h-4 w-4 text-primary-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z" />
                            </svg>
                            {{ $project->location }}
                        </p>
                        @if ($project->description)
                            <p class="mt-3 text-sm leading-relaxed text-navy-800/60">{{ $project->description }}</p>
                        @endif
                    </div>
                </article>
            @empty
                <p class="col-span-full text-center text-navy-800/50">Belum ada proyek untuk ditampilkan.</p>
            @endforelse
        </div>

        <div class="mt-12 text-center">
            <a href="{{ \App\Support\WhatsApp::link('Halo Kolam Prima, saya ingin melihat lebih banyak contoh proyek kolam renang.') }}"
               target="_blank" rel="noopener"
               class="inline-flex items-center gap-2 rounded-xl bg-navy-900 px-6 py-3.5 text-base font-semibold text-white shadow-lg transition hover:bg-navy-800">
                <x-icon.whatsapp class="h-5 w-5" />
                Minta Contoh Proyek Lain
            </a>
        </div>
    </div>
</section>

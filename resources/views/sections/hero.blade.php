<section id="beranda" class="relative overflow-hidden bg-gradient-to-b from-primary-50 via-white to-white pt-28 pb-16 sm:pt-32 lg:pt-40 lg:pb-24">
    {{-- Dekorasi latar --}}
    <div class="pointer-events-none absolute inset-x-0 top-0 -z-10 h-[500px] bg-[radial-gradient(60%_50%_at_50%_0%,rgba(2,132,199,0.12),transparent)]"></div>

    <div class="mx-auto grid max-w-7xl items-center gap-12 px-4 sm:px-6 lg:grid-cols-2 lg:gap-16 lg:px-8">
        {{-- Kolom teks --}}
        <div>
            <span class="inline-flex items-center gap-2 rounded-full border border-primary-200 bg-white px-4 py-1.5 text-xs font-semibold text-primary-700 shadow-sm">
                <span class="h-1.5 w-1.5 rounded-full bg-primary-500"></span>
                Desain · Bangun · Perawatan
            </span>

            <h1 class="mt-6 font-heading text-4xl font-extrabold leading-tight tracking-tight text-navy-900 sm:text-5xl lg:text-6xl">
                {{ $settings->hero_headline }}
            </h1>

            <p class="mt-6 max-w-xl text-lg leading-relaxed text-navy-800/70">
                {{ $settings->hero_subheadline }}
            </p>

            <div class="mt-8 flex flex-col gap-3 sm:flex-row">
                <a href="{{ \App\Support\WhatsApp::link('Halo Kolam Prima, saya ingin konsultasi gratis mengenai pembuatan kolam renang.') }}"
                   target="_blank" rel="noopener"
                   class="inline-flex items-center justify-center gap-2 rounded-xl bg-accent-500 px-6 py-3.5 text-base font-semibold text-navy-900 shadow-lg shadow-accent-500/20 transition hover:bg-accent-600 hover:text-white">
                    <x-icon.whatsapp class="h-5 w-5" />
                    Konsultasi Gratis
                </a>
                <a href="#portofolio"
                   class="inline-flex items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-6 py-3.5 text-base font-semibold text-navy-800 shadow-sm transition hover:border-primary-300 hover:text-primary-700">
                    Lihat Portofolio
                </a>
            </div>

            {{-- Poin kepercayaan --}}
            <dl class="mt-10 grid grid-cols-3 gap-6 border-t border-slate-100 pt-8">
                <div>
                    <dt class="text-xs font-medium uppercase tracking-wide text-navy-800/50">Garansi</dt>
                    <dd class="mt-1 font-heading text-lg font-bold text-navy-900">Terjamin</dd>
                </div>
                <div>
                    <dt class="text-xs font-medium uppercase tracking-wide text-navy-800/50">Area Layanan</dt>
                    <dd class="mt-1 font-heading text-lg font-bold text-navy-900">Jabodetabek+</dd>
                </div>
                <div>
                    <dt class="text-xs font-medium uppercase tracking-wide text-navy-800/50">Konsultasi</dt>
                    <dd class="mt-1 font-heading text-lg font-bold text-navy-900">Gratis</dd>
                </div>
            </dl>
        </div>

        {{-- Kolom gambar --}}
        <div class="relative">
            <div class="relative overflow-hidden rounded-3xl shadow-2xl shadow-primary-900/10 ring-1 ring-slate-900/5">
                <img src="{{ $projects->first()?->images->first()?->image_url ?? 'https://picsum.photos/seed/kolam-hero/1200/900' }}"
                     alt="Kolam renang impian" class="aspect-[4/3] w-full object-cover" loading="eager">
            </div>
            {{-- Kartu mengambang --}}
            <div class="absolute -bottom-5 -left-4 hidden rounded-2xl bg-white p-4 shadow-xl ring-1 ring-slate-900/5 sm:block">
                <div class="flex items-center gap-3">
                    <span class="grid h-11 w-11 place-items-center rounded-xl bg-primary-50 text-primary-600">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </span>
                    <div>
                        <p class="font-heading text-sm font-bold text-navy-900">Kualitas Terjamin</p>
                        <p class="text-xs text-navy-800/60">Dikerjakan tim berpengalaman</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<section id="paket" class="bg-muted py-16 sm:py-24">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <x-section-heading
            eyebrow="Paket Layanan"
            title="Pilih Paket Sesuai Kebutuhan"
            subtitle="Hubungi kami untuk penawaran harga terbaik sesuai kondisi proyek Anda."
        />

        <div class="mt-14 grid gap-6 lg:grid-cols-3">
            @foreach ($packages as $package)
                <div class="relative flex flex-col rounded-2xl bg-white p-8 shadow-sm ring-1 transition duration-300 hover:shadow-xl hover:shadow-primary-900/5
                    {{ $package->is_popular ? 'ring-2 ring-primary-500 lg:-translate-y-3' : 'ring-slate-900/5' }}">

                    @if ($package->is_popular)
                        <span class="absolute -top-3.5 left-1/2 -translate-x-1/2 rounded-full bg-primary-600 px-4 py-1.5 text-xs font-bold uppercase tracking-wide text-white shadow-md">
                            Paling Populer
                        </span>
                    @endif

                    <h3 class="font-heading text-xl font-bold text-navy-900">{{ $package->name }}</h3>
                    <p class="mt-2 text-sm text-navy-800/60">{{ $package->tagline }}</p>

                    <p class="mt-6 font-heading text-2xl font-extrabold {{ $package->is_popular ? 'text-primary-600' : 'text-navy-900' }}">
                        {{ $package->priceLabel() }}
                    </p>

                    <ul class="mt-6 flex-1 space-y-3">
                        @foreach ($package->features ?? [] as $feature)
                            <li class="flex items-start gap-3 text-sm text-navy-800/70">
                                <svg class="mt-0.5 h-5 w-5 flex-none text-primary-500" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                                </svg>
                                {{ $feature }}
                            </li>
                        @endforeach
                    </ul>

                    <a href="{{ \App\Support\WhatsApp::link('Halo Kolam Prima, saya ingin menanyakan harga paket ' . $package->name . '.') }}"
                       target="_blank" rel="noopener"
                       class="mt-8 inline-flex items-center justify-center gap-2 rounded-xl px-6 py-3.5 text-sm font-semibold transition
                        {{ $package->is_popular
                            ? 'bg-accent-500 text-navy-900 shadow-lg shadow-accent-500/20 hover:bg-accent-600 hover:text-white'
                            : 'border border-slate-200 text-navy-800 hover:border-primary-300 hover:text-primary-700' }}">
                        <x-icon.whatsapp class="h-4 w-4" />
                        Tanya Harga via WhatsApp
                    </a>
                </div>
            @endforeach
        </div>
    </div>
</section>

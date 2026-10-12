<section id="layanan" class="py-16 sm:py-24">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <x-section-heading
            eyebrow="Layanan Kami"
            title="Solusi Lengkap Kolam Renang"
            subtitle="Dari perencanaan hingga perawatan, semua kebutuhan kolam renang Anda ditangani satu tim."
        />

        <div class="mt-14 grid gap-6 md:grid-cols-2 lg:grid-cols-3">
            @foreach ($services as $service)
                <div class="group flex flex-col rounded-2xl border border-slate-100 bg-white p-7 shadow-sm transition duration-300 hover:-translate-y-1 hover:border-primary-200 hover:shadow-xl hover:shadow-primary-900/5">
                    <span class="grid h-14 w-14 place-items-center rounded-2xl bg-primary-50 text-primary-600 transition group-hover:bg-primary-600 group-hover:text-white">
                        <svg class="h-7 w-7" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                            @switch($service->icon)
                                @case('pencil-ruler')
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zM19.5 7.125L16.875 4.5M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" />
                                    @break
                                @case('hammer')
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M11.42 15.17L17.25 21A2.652 2.652 0 0021 17.25l-5.877-5.877M11.42 15.17l2.496-3.03c.317-.384.74-.626 1.208-.766M11.42 15.17l-4.655 5.653a2.548 2.548 0 11-3.586-3.586l6.837-5.63m5.108-.233c.55-.164 1.163-.188 1.743-.14a4.5 4.5 0 004.486-6.336l-3.276 3.277a3.004 3.004 0 01-2.25-2.25l3.276-3.276a4.5 4.5 0 00-6.336 4.486c.091 1.076-.071 2.264-.904 2.95l-.102.085m-1.745 1.437L5.909 7.5H4.5L2.25 3.75l1.5-1.5L7.5 4.5v1.409l4.26 4.26m-1.745 1.437l1.745-1.437m6.615 8.206L15.75 15.75M4.867 19.125h.008v.008h-.008v-.008z" />
                                    @break
                                @case('droplets')
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 12c0 4.142-3.358 7.5-7.5 7.5S4.5 16.142 4.5 12c0-3 3-6 7.5-10.5C16.5 6 19.5 9 19.5 12z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 13.5c.5 1.5 1.5 2.25 3 2.25" />
                                    @break
                                @default
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v18m9-9H3" />
                            @endswitch
                        </svg>
                    </span>

                    <h3 class="mt-6 font-heading text-xl font-bold text-navy-900">{{ $service->title }}</h3>
                    <p class="mt-3 flex-1 text-sm leading-relaxed text-navy-800/60">{{ $service->description }}</p>

                    <a href="{{ \App\Support\WhatsApp::link($service->wa_message) }}" target="_blank" rel="noopener"
                       class="mt-6 inline-flex items-center gap-2 text-sm font-semibold text-primary-600 transition hover:gap-3 hover:text-primary-700">
                        Tanya Layanan Ini
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12l-7.5 7.5M21 12H3" />
                        </svg>
                    </a>
                </div>
            @endforeach
        </div>
    </div>
</section>

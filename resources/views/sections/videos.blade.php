@if ($videos->isNotEmpty())
    <section id="video" class="py-16 sm:py-24" x-data="{ active: null }">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <x-section-heading
                eyebrow="Galeri Video"
                title="Lihat Proses & Hasilnya Bergerak"
                subtitle="Rekaman singkat dari pengerjaan dan hasil kolam renang yang kami bangun."
            />

            <div class="mt-14 grid grid-cols-2 gap-4 sm:gap-6 lg:grid-cols-3">
                @foreach ($videos as $video)
                    <button type="button"
                            @click="active = {{ Illuminate\Support\Js::from([
                                'url' => $video->url,
                                'title' => $video->title,
                            ]) }}"
                            class="group relative block overflow-hidden rounded-2xl bg-navy-900 shadow-sm ring-1 ring-slate-900/5 transition duration-300 hover:-translate-y-1 hover:shadow-xl">
                        <div class="aspect-[9/16] w-full">
                            @if ($video->thumbnail_url)
                                <img src="{{ $video->thumbnail_url }}"
                                     alt="{{ $video->title }}"
                                     loading="lazy"
                                     class="h-full w-full object-cover opacity-90 transition duration-500 group-hover:scale-105 group-hover:opacity-100">
                            @endif
                        </div>
                        <span class="pointer-events-none absolute inset-0 bg-gradient-to-t from-navy-950/80 via-navy-950/10 to-transparent"></span>
                        <span class="pointer-events-none absolute left-1/2 top-1/2 grid h-14 w-14 -translate-x-1/2 -translate-y-1/2 place-items-center rounded-full bg-white/90 text-primary-700 shadow-lg transition group-hover:scale-110">
                            <svg class="h-6 w-6 translate-x-0.5" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M8 5v14l11-7z" />
                            </svg>
                        </span>
                        <span class="pointer-events-none absolute inset-x-0 bottom-0 p-4 text-left">
                            <span class="font-heading text-sm font-semibold text-white drop-shadow sm:text-base">{{ $video->title }}</span>
                        </span>
                    </button>
                @endforeach
            </div>

            {{-- Modal pemutar video --}}
            <div x-show="active" x-cloak
                 @keydown.escape.window="active = null"
                 class="fixed inset-0 z-[60] flex items-center justify-center bg-navy-950/90 p-4 backdrop-blur-sm"
                 x-transition.opacity>
                <div class="relative w-full max-w-sm" @click.outside="active = null">
                    <button type="button" @click="active = null"
                            class="absolute -top-11 right-0 inline-flex items-center gap-1.5 rounded-lg bg-white/10 px-3 py-1.5 text-sm font-medium text-white transition hover:bg-white/20">
                        Tutup
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 6l12 12M18 6L6 18" />
                        </svg>
                    </button>
                    <template x-if="active">
                        <video :src="active.url" :aria-label="active.title"
                               class="max-h-[80vh] w-full rounded-2xl bg-black shadow-2xl"
                               controls autoplay playsinline></video>
                    </template>
                </div>
            </div>
        </div>
    </section>
@endif

@if ($faqs->isNotEmpty())
    <section id="faq" class="py-16 sm:py-24" x-data="{ open: null }">
        <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
            <x-section-heading
                eyebrow="Pertanyaan Umum"
                title="Hal yang Sering Ditanyakan"
                subtitle="Belum menemukan jawabannya? Hubungi kami lewat WhatsApp, kami balas secepatnya di jam kerja."
            />

            <div class="mt-14 divide-y divide-slate-200 overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-900/5">
                @foreach ($faqs as $faq)
                    <div x-data="{ id: {{ $faq->getKey() }} }">
                        <h3>
                            <button type="button"
                                    @click="open = (open === id ? null : id)"
                                    :aria-expanded="(open === id).toString()"
                                    aria-controls="faq-panel-{{ $faq->getKey() }}"
                                    class="flex w-full items-center justify-between gap-4 px-6 py-5 text-left transition hover:bg-muted">
                                <span class="font-heading text-base font-semibold text-navy-900 sm:text-lg">{{ $faq->question }}</span>
                                <span class="grid h-8 w-8 flex-none place-items-center rounded-full bg-primary-50 text-primary-700 transition"
                                      :class="open === id ? 'rotate-180 bg-primary-600 text-white' : ''">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
                                    </svg>
                                </span>
                            </button>
                        </h3>

                        <div id="faq-panel-{{ $faq->getKey() }}"
                             x-show="open === id"
                             x-cloak
                             x-transition.opacity
                             class="px-6 pb-6">
                            <p class="max-w-3xl text-sm leading-relaxed text-navy-800/70 sm:text-base">{{ $faq->answer }}</p>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="mt-10 text-center">
                <a href="{{ \App\Support\WhatsApp::defaultLink() }}" target="_blank" rel="noopener"
                   class="inline-flex items-center gap-2 rounded-xl bg-whatsapp px-7 py-4 text-base font-semibold text-white shadow-lg transition hover:bg-whatsapp-dark">
                    <x-icon.whatsapp class="h-5 w-5" />
                    Tanya Langsung via WhatsApp
                </a>
            </div>
        </div>
    </section>
@endif

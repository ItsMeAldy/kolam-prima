@php
    $waNumber = preg_replace('/\D+/', '', (string) $settings->whatsapp_number) ?? '';
@endphp

<section id="kontak" class="relative overflow-hidden bg-navy-900 py-16 sm:py-24">
    {{-- Dekorasi --}}
    <div class="pointer-events-none absolute inset-0 -z-0 opacity-20">
        <div class="absolute -left-24 top-0 h-96 w-96 rounded-full bg-primary-600 blur-3xl"></div>
        <div class="absolute -right-24 bottom-0 h-96 w-96 rounded-full bg-primary-500 blur-3xl"></div>
    </div>

    <div class="relative mx-auto grid max-w-7xl items-center gap-12 px-4 sm:px-6 lg:grid-cols-2 lg:gap-16 lg:px-8">
        {{-- Ajakan --}}
        <div>
            <x-section-heading
                align="left"
                light
                eyebrow="Mulai Sekarang"
                title="Siap Mewujudkan Kolam Renang Impian Anda?"
                subtitle="Konsultasi gratis, tanpa kewajiban. Tim kami siap membantu merencanakan kolam renang yang tepat untuk Anda."
            />

            <a href="{{ \App\Support\WhatsApp::defaultLink() }}" target="_blank" rel="noopener"
               class="mt-8 inline-flex items-center gap-2 rounded-xl bg-whatsapp px-7 py-4 text-base font-semibold text-white shadow-lg transition hover:bg-whatsapp-dark">
                <x-icon.whatsapp class="h-5 w-5" />
                Chat Langsung via WhatsApp
            </a>

            <ul class="mt-8 space-y-3">
                @foreach (['Respon cepat di jam kerja', 'Konsultasi tanpa biaya', 'Penawaran sesuai anggaran Anda'] as $point)
                    <li class="flex items-center gap-3 text-sm text-white/70">
                        <svg class="h-5 w-5 flex-none text-primary-400" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                        </svg>
                        {{ $point }}
                    </li>
                @endforeach
            </ul>
        </div>

        {{-- Form --}}
        <div
            x-data="{
                nama: '', telepon: '', kebutuhan: '', pesan: '',
                submit() {
                    const lines = [
                        'Halo {{ $settings->brand_name }}, saya ingin konsultasi.',
                        'Nama: ' + this.nama,
                        'Telepon: ' + this.telepon,
                        'Kebutuhan: ' + this.kebutuhan,
                    ];
                    if (this.pesan) { lines.push('Pesan: ' + this.pesan); }
                    const url = 'https://wa.me/{{ $waNumber }}?text=' + encodeURIComponent(lines.join('\n'));
                    window.open(url, '_blank');
                }
            }"
            class="rounded-2xl bg-white p-7 shadow-2xl sm:p-8"
        >
            <h3 class="font-heading text-xl font-bold text-navy-900">Formulir Konsultasi</h3>
            <p class="mt-1 text-sm text-navy-800/60">Isi data singkat, lalu lanjutkan ke WhatsApp.</p>

            <form @submit.prevent="submit()" class="mt-6 space-y-4">
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-navy-800">Nama</label>
                    <input type="text" x-model="nama" required
                           class="w-full rounded-xl border border-slate-200 px-4 py-3 text-sm outline-none transition focus:border-primary-500 focus:ring-2 focus:ring-primary-100"
                           placeholder="Nama Anda">
                </div>

                <div>
                    <label class="mb-1.5 block text-sm font-medium text-navy-800">Nomor Telepon</label>
                    <input type="tel" x-model="telepon" required pattern="[0-9+\-\s]{8,}"
                           class="w-full rounded-xl border border-slate-200 px-4 py-3 text-sm outline-none transition focus:border-primary-500 focus:ring-2 focus:ring-primary-100"
                           placeholder="08xxxxxxxxxx">
                </div>

                <div>
                    <label class="mb-1.5 block text-sm font-medium text-navy-800">Jenis Kebutuhan</label>
                    <select x-model="kebutuhan" required
                            class="w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm outline-none transition focus:border-primary-500 focus:ring-2 focus:ring-primary-100">
                        <option value="">Pilih kebutuhan</option>
                        <option>Desain Kolam</option>
                        <option>Pembangunan Kolam</option>
                        <option>Perawatan Kolam</option>
                        <option>Renovasi Kolam</option>
                        <option>Lainnya</option>
                    </select>
                </div>

                <div>
                    <label class="mb-1.5 block text-sm font-medium text-navy-800">Pesan <span class="text-navy-800/40">(opsional)</span></label>
                    <textarea x-model="pesan" rows="3"
                              class="w-full rounded-xl border border-slate-200 px-4 py-3 text-sm outline-none transition focus:border-primary-500 focus:ring-2 focus:ring-primary-100"
                              placeholder="Ceritakan kebutuhan Anda..."></textarea>
                </div>

                {{-- Honeypot --}}
                <div class="hidden" aria-hidden="true">
                    <input type="text" name="website" tabindex="-1" autocomplete="off">
                </div>

                <button type="submit"
                        class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-accent-500 px-6 py-3.5 text-base font-semibold text-navy-900 shadow-lg shadow-accent-500/20 transition hover:bg-accent-600 hover:text-white">
                    <x-icon.whatsapp class="h-5 w-5" />
                    Kirim & Lanjut ke WhatsApp
                </button>
            </form>
        </div>
    </div>
</section>

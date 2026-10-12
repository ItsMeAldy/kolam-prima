<header
    x-data="{ open: false, scrolled: false }"
    x-init="window.addEventListener('scroll', () => scrolled = window.scrollY > 20)"
    :class="scrolled ? 'bg-white/95 shadow-md backdrop-blur' : 'bg-transparent'"
    class="fixed inset-x-0 top-0 z-50 transition-all duration-300"
>
    <nav class="mx-auto flex max-w-7xl items-center justify-between px-4 py-4 sm:px-6 lg:px-8">
        {{-- Brand --}}
        <a href="#beranda" class="flex items-center gap-2">
            <span class="grid h-9 w-9 place-items-center rounded-xl bg-primary-600 text-white">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M2 15c1.5-1.5 3-1.5 4.5 0s3 1.5 4.5 0 3-1.5 4.5 0 3 1.5 4.5 0M2 9c1.5-1.5 3-1.5 4.5 0s3 1.5 4.5 0 3-1.5 4.5 0 3 1.5 4.5 0" />
                </svg>
            </span>
            <span class="font-heading text-lg font-bold tracking-tight">{{ $settings->brand_name }}</span>
        </a>

        {{-- Menu desktop --}}
        <div class="hidden items-center gap-8 lg:flex">
            @foreach ([
                ['#layanan', 'Layanan'],
                ['#portofolio', 'Portofolio'],
                ['#proses', 'Proses Kerja'],
                ['#paket', 'Paket'],
                ['#kontak', 'Kontak'],
            ] as [$href, $label])
                <a href="{{ $href }}" class="text-sm font-medium text-navy-800/80 transition hover:text-primary-600">{{ $label }}</a>
            @endforeach
        </div>

        {{-- Tombol WA desktop --}}
        <a href="{{ \App\Support\WhatsApp::defaultLink() }}" target="_blank" rel="noopener"
           class="hidden items-center gap-2 rounded-xl bg-whatsapp px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-whatsapp-dark lg:inline-flex">
            <x-icon.whatsapp class="h-4 w-4" />
            Hubungi Kami
        </a>

        {{-- Hamburger --}}
        <button @click="open = !open" class="rounded-lg p-2 text-navy-900 lg:hidden" aria-label="Menu">
            <svg x-show="!open" class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" d="M4 6h16M4 12h16M4 18h16" />
            </svg>
            <svg x-show="open" x-cloak class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" d="M6 6l12 12M18 6L6 18" />
            </svg>
        </button>
    </nav>

    {{-- Menu mobile --}}
    <div x-show="open" x-cloak x-transition class="border-t border-slate-100 bg-white lg:hidden">
        <div class="space-y-1 px-4 py-4">
            @foreach ([
                ['#layanan', 'Layanan'],
                ['#portofolio', 'Portofolio'],
                ['#proses', 'Proses Kerja'],
                ['#paket', 'Paket'],
                ['#kontak', 'Kontak'],
            ] as [$href, $label])
                <a href="{{ $href }}" @click="open = false" class="block rounded-lg px-3 py-2 text-sm font-medium text-navy-800 hover:bg-slate-50">{{ $label }}</a>
            @endforeach
            <a href="{{ \App\Support\WhatsApp::defaultLink() }}" target="_blank" rel="noopener"
               class="mt-2 flex items-center justify-center gap-2 rounded-xl bg-whatsapp px-5 py-3 text-sm font-semibold text-white">
                <x-icon.whatsapp class="h-4 w-4" />
                Hubungi Kami
            </a>
        </div>
    </div>
</header>

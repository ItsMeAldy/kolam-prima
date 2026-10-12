@extends('layouts.app')

@section('title', 'Halaman Tidak Ditemukan — ' . $settings->brand_name)
@section('description', 'Halaman yang Anda cari tidak ditemukan.')

@section('content')
    <section class="relative flex min-h-screen items-center justify-center overflow-hidden bg-gradient-to-b from-primary-50 to-white px-4">
        <div class="pointer-events-none absolute inset-x-0 top-0 -z-10 h-96 bg-[radial-gradient(50%_60%_at_50%_0%,rgba(2,132,199,0.12),transparent)]"></div>

        <div class="text-center">
            <p class="font-heading text-7xl font-extrabold text-primary-600 sm:text-9xl">404</p>
            <h1 class="mt-4 font-heading text-2xl font-bold text-navy-900 sm:text-3xl">Halaman Tidak Ditemukan</h1>
            <p class="mx-auto mt-4 max-w-md text-navy-800/60">
                Maaf, halaman yang Anda cari tidak ada atau sudah dipindahkan. Silakan kembali ke beranda.
            </p>
            <div class="mt-8 flex flex-col justify-center gap-3 sm:flex-row">
                <a href="{{ route('home') }}"
                   class="inline-flex items-center justify-center gap-2 rounded-xl bg-primary-600 px-6 py-3.5 text-base font-semibold text-white shadow-lg transition hover:bg-primary-700">
                    Kembali ke Beranda
                </a>
                <a href="{{ \App\Support\WhatsApp::defaultLink() }}" target="_blank" rel="noopener"
                   class="inline-flex items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-6 py-3.5 text-base font-semibold text-navy-800 transition hover:border-primary-300 hover:text-primary-700">
                    <x-icon.whatsapp class="h-5 w-5" />
                    Hubungi Kami
                </a>
            </div>
        </div>
    </section>
@endsection

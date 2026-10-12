# TODO — Kolam Prima (Lanjutan Fase 9 → 10)

Rujukan: `PLAN.md`, `DOKUMENTASI.md`, handoff 12 Okt 2026.
Status awal: Fase 0–9 hampir selesai; 1 bug (output buffer) + commit tertunda.

## A. Selesaikan Fase 9 (bugfix + commit)
- [x] A1. Perbaiki bug output buffer `@section('description', ...)`.
  - [x] A1a. Isi default non-null (`hero_headline`, `hero_subheadline`) di `SiteSetting::current()`.
  - [x] A1b. Guard di `home.blade.php` agar `description` selalu string.
- [x] A2. Tambah test regresi: `ob_get_level()` stabil setelah render halaman utama.
- [x] A3. Jalankan `php artisan test` → `19 passed, 62 assertions, 0 risky`.
- [x] A4. Commit Fase 9 (4 test baru + 2 test contoh dihapus).

## B. Sinkronisasi dokumen
- [x] B1. Update `PLAN.md`: tandai Fase 0–9 selesai.
- [x] B2. Hapus artefak screenshot verifikasi (`storage/app/home-*.png`, `404b.png`).

## C. Ditunda (tunggu instruksi user)
- [ ] C1. Fase 10 — Deployment.
- [x] C2. Hubungkan GitHub + push repo → https://github.com/ItsMeAldy/kolam-prima (public, branch `main`).
- [ ] C3. Opsional: Laravel Boost, Umami analytics, konten asli (nomor WA, alamat, foto).
- [ ] C4. Fase 2/3 lanjutan: blog, testimoni, FAQ, halaman layanan terpisah.

## D. Verifikasi manual (dilakukan user)
- [x] D1. Jalankan MySQL + `artisan serve`, cek landing page + panel admin di browser.
- [ ] D2. Isi temuan/feedback hasil pengecekan manual (jika ada).

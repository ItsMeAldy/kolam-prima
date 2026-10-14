# TODO — Kolam Prima (Lanjutan Fase 9 → 10)

Rujukan: `PLAN.md`, `DOKUMENTASI.md`, handoff 12 Okt 2026.
Terakhir diperbarui: 2026-10-14

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

## C. Media nyata & upload storage (Fase 9b)
- [x] C1. Aktifkan disk `public` + `php artisan storage:link`.
- [x] C2. Salin 15 foto + 5 video ke `storage/app/public/media/`.
- [x] C3. Poster video dari frame (ffmpeg).
- [x] C4. Migrasi `project_images.path` + tabel `videos`.
- [x] C5. Model `Video`, accessor `ProjectImage::url`, `Project::coverUrl()`.
- [x] C6. Filament: upload gambar di Proyek + resource Galeri Video.
- [x] C7. Section "Galeri Video" + anchor nav.
- [x] C8. Seeder proyek/video nyata; placeholder picsum dihapus.
- [x] C9. Test hijau (22 passed, 80 assertions) + Pint.
- [x] C10. Upload foto Hero + logo brand (Pengaturan Situs) & gambar ikon kartu Layanan.
  - [x] C10a. Migrasi `site_settings.hero_image` + `logo_path`; `services.image_path`.
  - [x] C10b. FileUpload di `ManageSiteSettings` + `ServiceForm`; accessor URL di model.
  - [x] C10c. Blade: hero pakai foto upload (fallback cover proyek), nav+footer pakai logo (fallback SVG), kartu layanan pakai gambar (fallback ikon).
  - [x] C10d. Test upload hero/logo/ikon + render; suite hijau (27 passed, 103 assertions) + Pint.

## D. Ditunda (tunggu instruksi user)
- [ ] D1. Fase 10 — Deployment.
- [x] D2. Hubungkan GitHub + push repo → https://github.com/ItsMeAldy/kolam-prima (public, branch `main`).
- [ ] D3. Isi judul/lokasi proyek & judul video yang benar (sekarang placeholder) lewat panel admin.
- [ ] D4. Opsional: Umami analytics, konten asli (nomor WA, alamat).
- [ ] D5. Fase 2/3 lanjutan: blog, testimoni, FAQ, halaman layanan terpisah.

## E. Verifikasi manual (dilakukan user)
- [x] E1. Jalankan MySQL + `artisan serve`, cek landing page + panel admin di browser.
- [x] E2. Cek galeri video di browser (poster tampil, modal play jalan) — diverifikasi via Playwright: 5 poster 200, modal play + Esc tutup OK.
- [x] E3. Isi temuan/feedback hasil pengecekan manual (jika ada).
  - [x] E3a. Admin tampil rusak (tanpa CSS/JS) → akar masalah: asset Filament belum dipublish. Fix: `php artisan filament:assets`. Semua asset 200.
  - [x] E3b. Thumbnail gambar di tabel Proyek kosong → `ImageColumn::make('images.0.path')` tak resolve indeks relasi. Fix: `getStateUsing(fn (Project $record) => $record->images->first()?->path)`.
  - [x] E3c. Label kolom "Gambar" duplikat di tabel Proyek → kolom jumlah diubah jadi "Jumlah Gambar".
  - [x] E3d. Asset Filament (`public/css|js|fonts/filament`, 39 file) di-commit ke git (opsi A). Wajib re-publish + commit ulang saat upgrade Filament.

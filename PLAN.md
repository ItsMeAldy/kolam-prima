# PLAN — Website Landing Page Jasa Kolam Renang "Kolam Prima"

> Dokumen ini adalah **rencana eksekusi**. Isinya: keputusan yang sudah dikunci,
> prasyarat, langkah kerja berurutan, dan definisi selesai.
> Untuk penjelasan teknis (arsitektur, skema database, design system), lihat `DOKUMENTASI.md`.

---

## 1. Ringkasan Proyek

Landing page **satu halaman** untuk bisnis jasa kolam renang (desain, pembangunan,
perawatan). Tujuan tunggal: mengarahkan pengunjung menghubungi via **WhatsApp**.
Dilengkapi **panel admin** (Filament) untuk mengelola konten tanpa menyentuh kode.

- **Fase yang dikerjakan sekarang**: Fase 1 — Website Inti (**selesai**, Fase 0–9 ✅).
- **Nama brand sementara**: Kolam Prima
- **Tagline**: "Desain, Bangun & Rawat Kolam Renang Impian Anda"
- **Audiens**: pemilik rumah, pengelola villa, hotel, properti komersial.

---

## 2. Keputusan yang Sudah Dikunci (Design Tree)

### 2.1 Stack & Environment

| Aspek | Keputusan |
|---|---|
| Framework | **Laravel 13** (butuh PHP ≥ 8.3) |
| Panel admin | **Filament 5** |
| Frontend | **Blade + Tailwind CSS + Alpine.js** |
| Database | **MySQL 8.4.3** (bawaan Laragon) |
| Runtime PHP | **Laragon PHP 8.3.30** (bukan Herd Lite) |
| Web server | **Laragon default (Apache)** |
| Composer | Update Composer Laragon ke versi terbaru |
| Lokasi proyek | `D:\laragon\www\website-kolam` → `website-kolam.test` |
| Git | init lokal; GitHub menyusul (ditunda) |
| Deploy | ditunda — kerjakan lokal dulu |
| Testing | **PHPUnit feature test** |
| Analytics | ditunda |

### 2.2 Bahasa & Desain

- Bahasa situs: **Indonesia saja**.
- Gaya: **clean, modern, premium** — bukan template generik.
- Palet:
  - Biru utama `#0284C7`
  - Navy (section gelap & teks) `#0F172A`
  - Aksen CTA `#F59E0B` (amber)
  - Hijau WhatsApp `#25D366` (khusus ikon/tombol WA)
  - Netral: putih + `#F8FAFC`
- Font: heading **Plus Jakarta Sans**, body **Inter**.

### 2.3 Struktur Halaman (10 bagian)

1. Navigasi (sticky)
2. Hero
3. Bilah Kepercayaan (4 angka)
4. Layanan (3 kartu)
5. Portofolio (grid 9 proyek)
6. Proses Kerja (6 langkah, hardcode)
7. Paket Layanan (3 paket)
8. Ajakan Akhir (tombol WA + form)
9. Footer
10. Tombol WhatsApp melayang

Plus: **halaman 404 custom**.

> **Catatan**: bagian **Testimoni** dan **FAQ** dari brief awal **dihapus** dari
> Fase 1 (keputusan Q16/Q21). Bisa ditambahkan kembali di Fase 2.

### 2.4 Panel Admin (4 resource + 1 pengaturan)

- **Proyek Portofolio** (dengan banyak gambar)
- **Paket Layanan**
- **Kartu Layanan**
- **Pengaturan Situs** (brand, kontak, sosmed, hero, 4 angka kepercayaan)
- Login: email + password, akun dibuat via seeder.

### 2.5 WhatsApp & Form

- Nomor WA disimpan di Pengaturan Situs, dipakai semua tombol.
- **Pesan prefill berbeda per konteks** (lihat `DOKUMENTASI.md` §9).
- Form konsultasi: nama, telepon, jenis kebutuhan, pesan → validasi + honeypot →
  redirect ke `wa.me` dengan pesan terisi. **Tanpa simpan ke database.**

### 2.6 Yang Tidak Dikerjakan di Fase 1

Dashboard prospek · penyimpanan lead · analytics · blog/artikel · Testimoni &
FAQ · halaman legal (privasi/syarat) · upload file (pakai URL) · bilingual ·
deployment.

---

## 3. Prasyarat (Cek Sebelum Mulai)

- [x] Laragon terpasang di `D:\laragon` — **sudah terverifikasi**.
- [x] MySQL 8.4.3 tersedia (`D:\laragon\bin\mysql\mysql-8.4.3-winx64`) — **ada**.
- [x] PHP 8.3.30 tersedia (`D:\laragon\bin\php\php-8.3.30-Win32-vs16-x64`) — **ada**.
- [x] Composer Laragon di-update ke versi terbaru (2.9.4).
- [x] Laragon dijalankan (Start All) → Apache + MySQL aktif.
- [x] Git terpasang — **ada** (`ItsMeAldy`).
- [x] (Opsional) Mailpit untuk cek email — tersedia di Laragon.

---

## 4. Langkah Eksekusi

Urutan ini dibuat agar tiap langkah bisa diverifikasi sebelum lanjut.

### Fase 0 — Persiapan Environment ✅
- [x] Update Composer Laragon: `composer self-update`
- [x] Jalankan Laragon → **Start All** (Apache + MySQL).
- [x] Buka Laragon Terminal, pastikan `php -v` = 8.3.30 dan `composer -V` terbaru.
- [x] Buat database kosong `website_kolam` di phpMyAdmin/HeidiSQL.

### Fase 1 — Scaffold Proyek Laravel 13 ✅
- [x] Scaffold Laravel 13 di `D:\laragon\www\website-kolam`.
- [x] Konfigurasi `.env`: koneksi MySQL (`DB_DATABASE=website_kolam`), `APP_URL=http://website-kolam.test`.
- [x] `php artisan key:generate`.
- [x] Verifikasi halaman default Laravel terbuka di `http://website-kolam.test`.
- [x] Inisialisasi git lokal + `.gitignore` + commit pertama.

### Fase 2 — Filament 5 & Autentikasi ✅
- [x] Install Filament 5.
- [x] Buat panel admin di `/admin`.
- [x] Konfigurasi autentikasi email + password.
- [x] Verifikasi halaman login Filament tampil.

### Fase 3 — Database (Migration & Model) ✅
- [x] Buat migration + model: `SiteSetting`, `Service`, `Package`, `Project`, `ProjectImage`.
- [x] Definisikan relasi (Project hasMany ProjectImage).
- [x] Jalankan `php artisan migrate`.
- [x] Verifikasi tabel terbentuk di phpMyAdmin.

### Fase 4 — Seeder ✅
- [x] Seeder akun admin: `admin@kolamprima.test` / `password`.
- [x] Seeder Pengaturan Situs (brand, kontak, hero, 4 angka kepercayaan).
- [x] Seeder 3 Kartu Layanan.
- [x] Seeder 3 Paket Layanan (satu bertanda "paling populer").
- [x] Seeder 9 Proyek Portofolio + gambar (URL Unsplash).
- [x] Jalankan `php artisan migrate:fresh --seed`, verifikasi data masuk.

### Fase 5 — Panel Admin (Filament Resources) ✅
- [x] `ProjectResource` + repeater gambar + upload/URL + urutan + publish.
- [x] `PackageResource` + repeater fitur + badge populer.
- [x] `ServiceResource` + urutan.
- [x] `SiteSettingResource` (satu record, halaman khusus).
- [x] Verifikasi CRUD tiap resource dari browser.

### Fase 6 — Landing Page (Blade + Tailwind) ✅
- [x] Setup Tailwind: palet warna, font, `tailwind.config.js`.
- [x] Layout utama (`layouts/app.blade.php`): meta, font, Alpine, slot.
- [x] Nav sticky + hamburger mobile + tombol WA.
- [x] Hero.
- [x] Bilah Kepercayaan.
- [x] Layanan.
- [x] Portofolio.
- [x] Proses Kerja.
- [x] Paket Layanan.
- [x] Ajakan Akhir + form.
- [x] Footer.
- [x] Tombol WA melayang.
- [x] Responsif diuji di lebar 375px, 768px, 1280px.

### Fase 7 — WhatsApp & Form ✅
- [x] Helper pembentuk URL `wa.me` + pesan prefill per konteks.
- [x] Pasang pesan spesifik di tiap tombol (layanan, paket, nav, melayang, akhir).
- [x] Form: validasi HTML5 + honeypot + redirect WA.
- [x] Uji semua tombol membuka WhatsApp dengan pesan benar.

### Fase 8 — 404 & SEO Dasar ✅
- [x] Halaman 404 custom bergaya brand.
- [x] Meta: title, description, Open Graph, favicon.
- [x] `sitemap.xml` + `robots.txt`.
- [x] JSON-LD `LocalBusiness`.

### Fase 9 — Testing ✅ (19 passed, 62 assertions, 0 risky)
- [x] Feature test: login admin berhasil/gagal.
- [x] Feature test: CRUD tiap resource Filament.
- [x] Feature test: halaman utama render 200 + data tampil.
- [x] Feature test: form menghasilkan URL WA benar.
- [x] `php artisan test` hijau.

### Fase 10 — Deployment (ditunda)
- [ ] Tunggu keputusan user (GitHub + hosting).
- [ ] Opsi bekal: Northflank (always-on, wajib kartu), Oracle Cloud Always Free
      (MySQL HeatWave 50 GiB), atau hosting berbayar murah.
- [ ] Siapkan `Dockerfile`/konfigurasi sesuai target.

---

## 5. Struktur Folder (Target)

```
website-kolam/
├── app/
│   ├── Filament/Resources/      # Project, Package, Service, SiteSetting
│   ├── Http/Controllers/        # HomeController
│   ├── Models/                  # Project, ProjectImage, Package, Service, SiteSetting, User
│   └── Support/                 # helper WhatsApp
├── database/
│   ├── migrations/
│   └── seeders/
├── resources/
│   ├── views/
│   │   ├── layouts/app.blade.php
│   │   ├── sections/            # nav, hero, trust, services, portfolio, ...
│   │   ├── home.blade.php
│   │   └── errors/404.blade.php
│   ├── css/app.css
│   └── js/app.js
├── routes/web.php
├── tests/Feature/
├── PLAN.md
└── DOKUMENTASI.md
```

---

## 6. Definisi Selesai (Definition of Done) — Fase 1

- [x] Semua 10 bagian landing page tampil, responsif, sesuai design system.
- [x] Semua tombol WhatsApp berfungsi dengan pesan prefill yang benar.
- [x] Form konsultasi mengarahkan ke WhatsApp dengan pesan terisi.
- [x] Admin bisa login dan CRUD seluruh 4 resource.
- [x] Halaman 404 custom tampil.
- [x] SEO dasar terpasang.
- [x] `php artisan test` hijau.
- [x] Konten placeholder jelas dan mudah diganti dari Pengaturan Situs.
- [x] Kode di-commit ke git lokal dengan riwayat rapi.

---

## 7. Risiko & Catatan

| Risiko | Mitigasi |
|---|---|
| Composer lama (2.6.6) bikin error | Update Composer sebelum scaffold |
| Ekosistem Laravel 13/Filament 5 masih tipis di tutorial | Siap baca dokumentasi resmi; tanya saat mentok |
| Free tier hosting berubah | Deploy ditunda; verifikasi ulang saat waktunya tiba |
| SQLite vs MySQL | Sudah diputuskan MySQL (Laragon) |
| Data hilang saat `migrate:fresh` | Seeder lengkap, jadi selalu bisa dibangun ulang |

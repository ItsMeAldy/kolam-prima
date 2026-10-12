# PLAN — Website Landing Page Jasa Kolam Renang "Kolam Prima"

> Dokumen ini adalah **rencana eksekusi**. Isinya: keputusan yang sudah dikunci,
> prasyarat, langkah kerja berurutan, dan definisi selesai.
> Untuk penjelasan teknis (arsitektur, skema database, design system), lihat `DOKUMENTASI.md`.

---

## 1. Ringkasan Proyek

Landing page **satu halaman** untuk bisnis jasa kolam renang (desain, pembangunan,
perawatan). Tujuan tunggal: mengarahkan pengunjung menghubungi via **WhatsApp**.
Dilengkapi **panel admin** (Filament) untuk mengelola konten tanpa menyentuh kode.

- **Fase yang dikerjakan sekarang**: Fase 1 — Website Inti.
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

- [ ] Laragon terpasang di `D:\laragon` — **sudah terverifikasi**.
- [ ] MySQL 8.4.3 tersedia (`D:\laragon\bin\mysql\mysql-8.4.3-winx64`) — **ada**.
- [ ] PHP 8.3.30 tersedia (`D:\laragon\bin\php\php-8.3.30-Win32-vs16-x64`) — **ada**.
- [ ] Composer Laragon di-update ke versi terbaru.
- [ ] Laragon dijalankan (Start All) → Apache + MySQL aktif.
- [ ] Git terpasang — **ada** (`ItsMeAldy`).
- [ ] (Opsional) Mailpit untuk cek email — tersedia di Laragon.

---

## 4. Langkah Eksekusi

Urutan ini dibuat agar tiap langkah bisa diverifikasi sebelum lanjut.

### Fase 0 — Persiapan Environment
- [ ] Update Composer Laragon: `composer self-update`
- [ ] Jalankan Laragon → **Start All** (Apache + MySQL).
- [ ] Buka Laragon Terminal, pastikan `php -v` = 8.3.30 dan `composer -V` terbaru.
- [ ] Buat database kosong `website_kolam` di phpMyAdmin/HeidiSQL.

### Fase 1 — Scaffold Proyek Laravel 13
- [ ] Scaffold Laravel 13 di `D:\laragon\www\website-kolam`.
- [ ] Konfigurasi `.env`: koneksi MySQL (`DB_DATABASE=website_kolam`), `APP_URL=http://website-kolam.test`.
- [ ] `php artisan key:generate`.
- [ ] Verifikasi halaman default Laravel terbuka di `http://website-kolam.test`.
- [ ] Inisialisasi git lokal + `.gitignore` + commit pertama.

### Fase 2 — Filament 5 & Autentikasi
- [ ] Install Filament 5.
- [ ] Buat panel admin di `/admin`.
- [ ] Konfigurasi autentikasi email + password.
- [ ] Verifikasi halaman login Filament tampil.

### Fase 3 — Database (Migration & Model)
- [ ] Buat migration + model: `SiteSetting`, `Service`, `Package`, `Project`, `ProjectImage`.
- [ ] Definisikan relasi (Project hasMany ProjectImage).
- [ ] Jalankan `php artisan migrate`.
- [ ] Verifikasi tabel terbentuk di phpMyAdmin.

### Fase 4 — Seeder
- [ ] Seeder akun admin: `admin@kolamprima.test` / `password`.
- [ ] Seeder Pengaturan Situs (brand, kontak, hero, 4 angka kepercayaan).
- [ ] Seeder 3 Kartu Layanan.
- [ ] Seeder 3 Paket Layanan (satu bertanda "paling populer").
- [ ] Seeder 9 Proyek Portofolio + gambar (URL Unsplash).
- [ ] Jalankan `php artisan migrate:fresh --seed`, verifikasi data masuk.

### Fase 5 — Panel Admin (Filament Resources)
- [ ] `ProjectResource` + repeater gambar + upload/URL + urutan + publish.
- [ ] `PackageResource` + repeater fitur + badge populer.
- [ ] `ServiceResource` + urutan.
- [ ] `SiteSettingResource` (satu record, halaman khusus).
- [ ] Verifikasi CRUD tiap resource dari browser.

### Fase 6 — Landing Page (Blade + Tailwind)
- [ ] Setup Tailwind: palet warna, font, `tailwind.config.js`.
- [ ] Layout utama (`layouts/app.blade.php`): meta, font, Alpine, slot.
- [ ] Nav sticky + hamburger mobile + tombol WA.
- [ ] Hero.
- [ ] Bilah Kepercayaan.
- [ ] Layanan.
- [ ] Portofolio.
- [ ] Proses Kerja.
- [ ] Paket Layanan.
- [ ] Ajakan Akhir + form.
- [ ] Footer.
- [ ] Tombol WA melayang.
- [ ] Responsif diuji di lebar 375px, 768px, 1280px.

### Fase 7 — WhatsApp & Form
- [ ] Helper pembentuk URL `wa.me` + pesan prefill per konteks.
- [ ] Pasang pesan spesifik di tiap tombol (layanan, paket, nav, melayang, akhir).
- [ ] Form: validasi HTML5 + honeypot + redirect WA.
- [ ] Uji semua tombol membuka WhatsApp dengan pesan benar.

### Fase 8 — 404 & SEO Dasar
- [ ] Halaman 404 custom bergaya brand.
- [ ] Meta: title, description, Open Graph, favicon.
- [ ] `sitemap.xml` + `robots.txt`.
- [ ] JSON-LD `LocalBusiness`.

### Fase 9 — Testing
- [ ] Feature test: login admin berhasil/gagal.
- [ ] Feature test: CRUD tiap resource Filament.
- [ ] Feature test: halaman utama render 200 + data tampil.
- [ ] Feature test: form menghasilkan URL WA benar.
- [ ] `php artisan test` hijau.

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

- [ ] Semua 10 bagian landing page tampil, responsif, sesuai design system.
- [ ] Semua tombol WhatsApp berfungsi dengan pesan prefill yang benar.
- [ ] Form konsultasi mengarahkan ke WhatsApp dengan pesan terisi.
- [ ] Admin bisa login dan CRUD seluruh 4 resource.
- [ ] Halaman 404 custom tampil.
- [ ] SEO dasar terpasang.
- [ ] `php artisan test` hijau.
- [ ] Konten placeholder jelas dan mudah diganti dari Pengaturan Situs.
- [ ] Kode di-commit ke git lokal dengan riwayat rapi.

---

## 7. Risiko & Catatan

| Risiko | Mitigasi |
|---|---|
| Composer lama (2.6.6) bikin error | Update Composer sebelum scaffold |
| Ekosistem Laravel 13/Filament 5 masih tipis di tutorial | Siap baca dokumentasi resmi; tanya saat mentok |
| Free tier hosting berubah | Deploy ditunda; verifikasi ulang saat waktunya tiba |
| SQLite vs MySQL | Sudah diputuskan MySQL (Laragon) |
| Data hilang saat `migrate:fresh` | Seeder lengkap, jadi selalu bisa dibangun ulang |

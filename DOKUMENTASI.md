# DOKUMENTASI — Website Landing Page Jasa Kolam Renang "Kolam Prima"

> Dokumen teknis lengkap: arsitektur, skema database, rute, design system,
> integrasi WhatsApp, SEO, testing, dan catatan deployment.
> Untuk urutan pengerjaan, lihat `PLAN.md`.

---

## 1. Ikhtisar

Aplikasi **Laravel** tunggal yang menyajikan:

1. **Landing page publik** (`/`) — satu halaman, fokus konversi ke WhatsApp.
2. **Panel admin** (`/admin`) — Filament, untuk mengelola konten.

Semua konten dinamis (proyek, paket, kartu layanan, pengaturan situs) berasal dari
database dan dapat diedit admin. Konten statis (proses kerja, teks struktural)
ditulis langsung di Blade.

---

## 2. Tech Stack

| Lapisan | Teknologi | Versi |
|---|---|---|
| Framework | Laravel | 13.x |
| Bahasa | PHP | 8.3.30 |
| Panel admin | Filament | 5.x |
| Frontend | Blade + Tailwind CSS + Alpine.js | — |
| Database | MySQL | 8.4.3 |
| Web server (dev) | Apache (Laragon) | — |
| Testing | PHPUnit (bawaan Laravel) | — |

**Kenapa Laravel + Filament?** Pengguna sudah familier Laravel; Filament
menghilangkan sebagian besar pekerjaan membangun panel admin (tabel, form,
filter, autentikasi) sehingga fokus bisa diarahkan ke landing page.

---

## 3. Arsitektur Aplikasi

```
┌─────────────────────────────────────────────────────────┐
│                      Browser                            │
└───────────────┬─────────────────────┬───────────────────┘
                │                     │
        (publik)│                     │(admin, login)
                ▼                     ▼
      ┌──────────────────┐   ┌──────────────────┐
      │  Landing Page    │   │  Panel Filament  │
      │  Blade + Tailwind│   │  /admin          │
      │  Alpine.js       │   │  CRUD resources  │
      └────────┬─────────┘   └────────┬─────────┘
               │                      │
               ▼                      ▼
      ┌──────────────────────────────────────────┐
      │           Eloquent ORM (Models)          │
      └───────────────────┬──────────────────────┘
                          ▼
                 ┌──────────────────┐
                 │   MySQL 8.4.3    │
                 └──────────────────┘

      Aksi WhatsApp: link keluar `wa.me` (tanpa backend)
```

**Prinsip arsitektur:**

- **Server-rendered** (Blade). Tidak ada SPA. JS minimal untuk interaksi.
- **Satu sumber kebenaran**: konten dinamis hanya dari database.
- **Tanpa penyimpanan lead**: form hanya menyusun pesan lalu membuka WhatsApp.
- **Akses admin dilindungi** autentikasi Filament.

---

## 4. Skema Database

### 4.1 `users` (bawaan Laravel)
| Kolom | Tipe | Catatan |
|---|---|---|
| id | bigint PK | |
| name | string | |
| email | string unique | admin: `admin@kolamprima.test` |
| password | string | hash bcrypt |
| timestamps | | |

### 4.2 `site_settings` (satu baris)
| Kolom | Tipe | Catatan |
|---|---|---|
| id | bigint PK | |
| brand_name | string | "Kolam Prima" |
| tagline | string | "Desain, Bangun & Rawat Kolam Renang Impian Anda" |
| whatsapp_number | string | format `62812xxxxxxx` (tanpa `+`, tanpa `0` depan) |
| email | string nullable | |
| address | text nullable | |
| instagram_url | string nullable | |
| tiktok_url | string nullable | |
| hero_headline | string | judul hero |
| hero_subheadline | text | subjudul hero |
| hero_image | string nullable | path foto hero di disk `public` (upload) |
| logo_path | string nullable | path logo brand di disk `public` (upload) |
| stat_1_label | string | mis. "Proyek Selesai" |
| stat_1_value | string | mis. "150+" |
| stat_2_label | string | mis. "Tahun Pengalaman" |
| stat_2_value | string | mis. "12" |
| stat_3_label | string | mis. "Klien Puas" |
| stat_3_value | string | mis. "120+" |
| stat_4_label | string | mis. "Kota Layanan" |
| stat_4_value | string | mis. "8" |
| timestamps | | |

> Pola: satu baris global. Admin mengedit lewat halaman khusus (bukan tabel daftar).

### 4.3 `services` (kartu layanan)
| Kolom | Tipe | Catatan |
|---|---|---|
| id | bigint PK | |
| title | string | "Desain & Gambar" |
| description | text | penjelasan singkat |
| icon | string nullable | nama ikon bawaan (fallback bila tak ada gambar) |
| image_path | string nullable | path gambar ikon di disk `public` (upload) |
| wa_message | string nullable | pesan prefill khusus kartu ini |
| sort_order | integer | urutan tampil |
| timestamps | | |

### 4.4 `packages` (paket layanan)
| Kolom | Tipe | Catatan |
|---|---|---|
| id | bigint PK | |
| name | string | "Desain + Bangun" |
| tagline | string nullable | ringkasan satu baris |
| features | json | array string (repeater) |
| price | string nullable | kosong = tampil "Hubungi kami" |
| is_popular | boolean | badge "Paling Populer" |
| sort_order | integer | |
| timestamps | | |

### 4.5 `projects` (portofolio)
| Kolom | Tipe | Catatan |
|---|---|---|
| id | bigint PK | |
| title | string | nama proyek |
| slug | string unique | untuk URL/identifikasi |
| location | string | kota/lokasi |
| property_type | enum | `rumah` \| `villa` \| `hotel` |
| description | text nullable | keterangan singkat |
| sort_order | integer | |
| is_published | boolean | tampil/tidak |
| timestamps | | |

### 4.6 `project_images`
| Kolom | Tipe | Catatan |
|---|---|---|
| id | bigint PK | |
| project_id | bigint FK → projects | cascade delete |
| image_url | string nullable | URL gambar eksternal (fallback lama) |
| path | string nullable | path berkas di disk `public` (upload) |
| sort_order | integer | |
| timestamps | | |

**Relasi:** `Project hasMany ProjectImage`; `ProjectImage belongsTo Project`.
Accessor `ProjectImage::url` mengutamakan `path` (berkas upload), jatuh ke `image_url`.

### 4.7 `videos` (galeri video)
| Kolom | Tipe | Catatan |
|---|---|---|
| id | bigint PK | |
| title | string | judul video |
| video_path | string | path berkas MP4 di disk `public` |
| thumbnail_path | string nullable | path poster (dibuat dari frame video) |
| sort_order | integer | |
| is_published | boolean | tampil/tidak |
| timestamps | | |

Accessor `Video::url` dan `Video::thumbnail_url` menghasilkan URL publik.

> **Upload file aktif (Fase 2):** gambar & video diunggah ke disk `public`
> (`storage/app/public/media/...`), diakses via `public/storage` (symlink
> `php artisan storage:link`). Berkas media bersifat runtime — di-ignore git.
> Bila `storage:link` belum dibuat, gambar tidak tampil di web.
>
> Jenis unggahan yang didukung: gambar proyek (`media/projects`), berkas video
> (`media/videos`) + poster (`media/thumbs`), foto hero (`media/hero`), logo
> brand (`media/branding`), dan gambar kartu layanan (`media/services`).

---

## 5. Model

| Model | Tabel | Catatan |
|---|---|---|
| `User` | users | bawaan Laravel |
| `SiteSetting` | site_settings | helper statis `current()` mengembalikan baris pertama |
| `Service` | services | scope urut `sort_order` |
| `Package` | packages | `features` di-cast ke array |
| `Project` | projects | `property_type` enum; `is_published` cast boolean |
| `ProjectImage` | project_images | relasi ke Project |

**Konvensi:** semua model dengan `sort_order` punya scope `ordered()`.

---

## 6. Rute

| Method | URI | Handler | Keterangan |
|---|---|---|---|
| GET | `/` | `HomeController@index` | landing page |
| GET | `/admin` | Filament | panel admin (login) |
| GET | `/sitemap.xml` | closure/controller | sitemap |
| GET | `/robots.txt` | file statis | |
| — | 404 | `errors/404.blade.php` | halaman tidak ditemukan |

Semua anchor navigasi (`#layanan`, `#portofolio`, `#proses`, `#paket`, `#kontak`)
mengarah ke section di halaman yang sama.

---

## 7. Design System

### 7.1 Palet Warna

| Peran | Hex | Tailwind (usulan) |
|---|---|---|
| Biru utama | `#0284C7` | `primary` |
| Biru tua / navy | `#0F172A` | `dark` |
| Aksen CTA | `#F59E0B` | `accent` |
| Hijau WhatsApp | `#25D366` | `whatsapp` |
| Latar terang | `#F8FAFC` | `muted` |
| Putih | `#FFFFFF` | `white` |

Aturan pemakaian:
- **Biru utama** untuk elemen brand, tautan, aksen sekunder.
- **Amber** untuk tombol ajakan utama (kontras tinggi terhadap biru).
- **Hijau WA** hanya untuk ikon/tombol yang membuka WhatsApp.
- **Navy** untuk section gelap (mis. Ajakan Akhir, Footer).

### 7.2 Tipografi

| Peran | Font | Bobot |
|---|---|---|
| Heading | Plus Jakarta Sans | 600–800 |
| Body | Inter | 400–500 |

Skala usulan: `text-sm` 14, `base` 16, `lg` 18, `xl` 20, `2xl` 24, `3xl` 30,
`4xl` 36, `5xl` 48.

### 7.3 Spacing & Radius

- Section: padding vertikal `py-16` (mobile) → `py-24` (desktop).
- Container: `max-w-7xl mx-auto px-4 sm:px-6 lg:px-8`.
- Radius: kartu `rounded-2xl`, tombol `rounded-xl`, badge `rounded-full`.
- Bayangan: `shadow-sm` s/d `shadow-lg`, halus.

### 7.4 Komponen Berulang

- **Tombol primer**: latar amber, teks navy/putih, `hover` lebih gelap.
- **Tombol WA**: latar hijau WA, ikon WA + teks.
- **Kartu**: putih, radius besar, shadow halus, hover naik sedikit.
- **Section heading**: label kecil (uppercase, biru) + judul besar + subjudul.
- **Badge**: pill, untuk "Paling Populer" dan tag jenis properti.

---

## 8. Struktur Landing Page (Detail per Bagian)

### 8.1 Navigasi (sticky)
- Kiri: logo teks "Kolam Prima".
- Tengah/kanan: menu **Layanan · Portofolio · Proses Kerja · Paket · Kontak**.
- Kanan: tombol WhatsApp.
- Mobile: hamburger (Alpine.js) membuka menu.
- Sticky saat scroll, latar putih + shadow tipis.

### 8.2 Hero
- Headline dari `hero_headline`.
- Subjudul dari `hero_subheadline`.
- Dua tombol: **Konsultasi Gratis (WA)** + **Lihat Portofolio** (`#portofolio`).
- Poin kepercayaan singkat (garansi, area layanan).
- Satu gambar besar kolam renang: dari `hero_image` (upload) bila ada, jika tidak
  jatuh ke foto proyek terbaru.

### 8.3 Bilah Kepercayaan
- Empat angka dari `stat_1..stat_4` (label + nilai).
- Tampil sejajar di desktop, grid 2×2 di mobile.

### 8.4 Layanan
- Tiga kartu dari tabel `services`.
- Tiap kartu: ikon/gambar, judul, deskripsi, tautan WA (`wa_message`).

### 8.5 Portofolio
- Grid dari `projects` (hanya `is_published`), urut `sort_order`.
- Tiap item: gambar utama (dari `project_images` pertama via `coverUrl()`),
  judul, lokasi, badge jenis properti.
- Gambar dari berkas upload (disk `public`) atau URL eksternal (fallback).

### 8.6 Galeri Video
- Grid dari `videos` (hanya `is_published`), urut `sort_order`.
- Tiap kartu: poster (`thumbnail_path`) rasio 9:16 + tombol play.
- Klik kartu membuka modal pemutar video (Alpine.js), tekan Esc untuk tutup.
- Section hanya dirender bila ada video.

### 8.7 Proses Kerja (hardcode di Blade)
6 langkah berurutan:
1. Konsultasi awal
2. Survei lokasi
3. Pembuatan desain & rencana anggaran
4. Pembayaran uang muka & pelaksanaan konstruksi
5. Serah terima proyek
6. Perawatan berkala (opsional)

Tampil sebagai timeline vertikal (mobile) / horizontal (desktop).

### 8.8 Paket Layanan
- Tiga kartu dari `packages`.
- Tiap kartu: nama, tagline, daftar fitur, harga atau "Hubungi kami",
  tombol "Tanya Harga via WA".
- Paket `is_popular` diberi badge **"Paling Populer"** + highlight.

### 8.9 Ajakan Akhir + Form
- Tombol WhatsApp besar.
- Form: **nama, nomor telepon, jenis kebutuhan, pesan**.
- Submit → susun pesan → buka `wa.me` (prefilled). Tanpa simpan DB.
- Validasi HTML5 + honeypot sederhana.

### 8.10 Footer
- Brand + tagline.
- Daftar layanan (tautan anchor).
- Kontak: WhatsApp, email, alamat.
- Sosmed: Instagram, TikTok.

### 8.11 Tombol WhatsApp Melayang
- Tetap di sudut kanan bawah, semua bagian.
- Pesan prefill umum.

---

## 9. Integrasi WhatsApp

### 9.1 Format URL
```
https://wa.me/{nomor}?text={pesan-ter-encode}
```
- `nomor`: hanya digit, kode negara tanpa `+` (mis. `6281234567890`).
- `pesan`: `urlencode()` pesan Bahasa Indonesia.

### 9.2 Pesan Prefill per Konteks

| Konteks | Pesan usulan |
|---|---|
| Tombol umum / nav / melayang | "Halo Kolam Prima, saya ingin bertanya tentang layanan kolam renang." |
| Kartu Layanan | "Halo, saya tertarik dengan layanan **{nama layanan}**." |
| Paket Layanan | "Halo, saya ingin menanyakan harga paket **{nama paket}**." |
| Hero (konsultasi gratis) | "Halo, saya ingin konsultasi gratis mengenai pembuatan kolam renang." |
| Form konsultasi | "Halo, nama saya {nama}. Saya butuh {jenis kebutuhan}. {pesan}. Nomor saya {telepon}." |

> Semua pesan dapat diubah dari kolom terkait (mis. `services.wa_message`)
> atau konstanta di helper WhatsApp.

### 9.3 Helper
Sebuah helper/service `WhatsApp::link(string $message, ?string $number = null)`
yang membaca nomor dari `SiteSetting` bila tidak diberikan, lalu mengembalikan
URL siap pakai. Semua tombol memanggil helper ini agar konsisten.

---

## 10. Form Konsultasi

**Field:**
| Field | Tipe | Validasi |
|---|---|---|
| nama | text | wajib |
| telepon | tel | wajib, pola angka |
| jenis_kebutuhan | select | wajib (Desain / Bangun / Perawatan / Lainnya) |
| pesan | textarea | opsional |

**Perilaku:**
1. Validasi HTML5 di sisi klien.
2. Honeypot (field tersembunyi) untuk menangkal bot.
3. JavaScript menyusun pesan, membuka `wa.me` di tab baru.
4. Tidak ada pengiriman ke server, tidak ada penyimpanan.

---

## 11. Panel Admin (Filament)

| Resource | Field utama | Aksi |
|---|---|---|
| **Proyek** | judul, slug, lokasi, jenis properti, deskripsi, gambar (repeater upload), urutan, publish | CRUD + atur urutan |
| **Paket** | nama, tagline, fitur (repeater), harga, populer, urutan | CRUD |
| **Layanan** | judul, deskripsi, ikon (gambar upload / nama Heroicon), pesan WA, urutan | CRUD |
| **Galeri Video** | judul, berkas video (MP4), poster, urutan, publish | CRUD |
| **Pengaturan Situs** | brand, logo (upload), tagline, WA, email, alamat, IG, TikTok, hero (teks + foto upload), 4 angka | Halaman edit tunggal |

- Autentikasi: email + password (Filament panel).
- Akun awal: `admin@kolamprima.test` / `password` (dibuat via seeder; segera ganti).

---

## 12. SEO Dasar

- **Title & description** per halaman (landing: brand + layanan + kota).
- **Open Graph** (`og:title`, `og:description`, `og:image`) untuk berbagi sosial.
- **Favicon**.
- **`sitemap.xml`** dan **`robots.txt`**.
- **JSON-LD `LocalBusiness`** — nama, alamat, telepon, jam, URL.

---

## 13. Testing

Feature test (PHPUnit) yang direncanakan:

1. **Landing page** — status 200, data proyek/paket/layanan tampil.
2. **Autentikasi admin** — login sukses, login gagal ditolak.
3. **CRUD resource** — create/update/delete tiap resource Filament.
4. **Form WhatsApp** — input valid menghasilkan URL `wa.me` dengan pesan benar.
5. **404** — URL tak dikenal menampilkan halaman custom.

Perintah: `php artisan test`.

---

## 14. Konvensi Kode

- **PSR-12** + gaya Laravel (Pint).
- Penamaan: model tunggal (`Project`), tabel jamak (`projects`).
- View dipecah per section: `resources/views/sections/*.blade.php`.
- Logika query di controller/scope, bukan di Blade.
- Semua teks antarmuka Bahasa Indonesia.
- Komponen Blade untuk elemen berulang (tombol WA, kartu).

---

## 15. Setup Lokal (Cara Menjalankan)

1. Jalankan **Laragon** → Start All.
2. Buka Laragon Terminal di `D:\laragon\www\website-kolam`.
3. Salin `.env.example` → `.env`, sesuaikan koneksi MySQL.
4. `composer install`
5. `php artisan key:generate`
6. `php artisan migrate:fresh --seed`
7. Akses `http://website-kolam.test` (landing) dan
   `http://website-kolam.test/admin` (admin).

---

## 16. Catatan Deployment (Ditunda)

Hasil riset free tier (Okt 2026):

| Opsi | Karakter |
|---|---|
| **Northflank** Developer Sandbox | Always-on, MySQL addon tersedia, **wajib kartu kredit** |
| **Oracle Cloud Always Free** | MySQL HeatWave 50 GiB, always-on, setup jaringan lebih ribet |
| **Aiven** | MySQL gratis 1 GB, **sleep saat idle** |
| **TiDB Serverless** | MySQL-compatible, **scale-to-zero** |
| **Railway** | Tanpa free tier sejati ($1 kredit/bulan) |
| **Render/Koyeb** | Sleep saat idle, disk tidak persisten |
| **Laravel Cloud** | Trial 30 hari, lalu berbayar |

> Keputusan deploy menyusul. Verifikasi ulang ketentuan free tier sebelum commit.

---

## 17. Roadmap Lanjutan

- **Fase 2 — Pengayaan Konten**: tambah banyak foto nyata, testimoni asli,
  artikel/blog singkat untuk SEO, upload gambar ke storage.
- **Fase 3 — Ekspansi**: halaman terpisah per layanan, sistem manajemen prospek,
  analytics lanjutan, bilingual bila diperlukan.

---

## 18. Lampiran — Daftar Keputusan (ringkas)

| # | Topik | Keputusan |
|---|---|---|
| 1 | Framework | Laravel 13 |
| 2 | Admin | Filament 5 |
| 3 | Frontend | Blade + Tailwind + Alpine |
| 4 | Database | MySQL 8.4.3 (Laragon) |
| 5 | Bahasa | Indonesia |
| 6 | Gaya | Clean modern premium |
| 7 | Testimoni & FAQ | Dihapus dari Fase 1 |
| 8 | Proses Kerja | Hardcode |
| 9 | Bilah Kepercayaan | Editable (Pengaturan Situs) |
| 10 | Form | Redirect WhatsApp, tanpa simpan |
| 11 | Upload gambar | Upload berkas ke disk `public` (proyek, video, hero, logo, ikon layanan) |
| 12 | Analytics | Ditunda |
| 13 | SEO dasar | Ya |
| 14 | Testing | Ya (PHPUnit) |
| 15 | Deploy | Ditunda |
| 16 | Lokasi | `D:\laragon\www\website-kolam` |
| 17 | Brand | Kolam Prima (placeholder) |

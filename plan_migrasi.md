
# PLAN MIGRASI — Patch Runtime PHP 8.3.30 → 8.5.12 (php2shell)

> **Status:** BELUM DIEKSEKUSI
> **Terakhir diperbarui:** 2026-11-13 (revisi: checkpoint sebelum Fase 0, PowerShell konsisten, rollback per fase, batas klaim audit)
> **Pemilik:** operator mesin lokal (Laragon `D:\laragon`)
> **Sifat dokumen:** runbook eksekusi. Setiap langkah punya tujuan, perintah persis,
> hasil yang diharapkan, dan jalan pulang.
> **Rujukan terkait:** `DOKUMENTASI.md` (§2 tech stack, §11 panel admin, §13 testing),
> `TODO.md` (D1 Fase 10 — Deployment), `PLAN.md`.
>
> **Ruang lingkup revisi 13 Nov 2026:** dokumen ini sekarang punya **dua fase**.
> Fase 0 (§5.4) menutup 13 advisory `composer audit` di 7 paket — dikerjakan **di PHP 8.3.30
> yang sekarang**, sebelum runtime disentuh. Fase 1 (§6) tetap upgrade runtime ke PHP 8.5.12.
> Urutan ini memisahkan perubahan dependency dan runtime agar regresi lebih mudah diisolasi.
> **Gate:** baseline harus direkam ulang dan checkpoint dibuat sebelum Fase 0. Platform
> dependency saat ini belum konsisten; bila baseline test gagal, jangan menganggapnya
> regresi update dan jangan memaksakan urutan ini tanpa meninjau penyebabnya.
>
> **Konvensi eksekusi:** semua blok perintah memakai **PowerShell**, bukan CMD/Bash.
> Dokumen ini bukan bukti bahwa migrasi atau seluruh pemeriksaan sudah dijalankan.

> **Catatan penamaan:** dokumen ini bernama `plan_migrasi.md` karena berawal dari
> pertanyaan "migrasi Laravel ke bahasa lain". Kesimpulannya: **yang dimigrasikan
> adalah versi runtime PHP, bukan bahasanya.** Evaluasi lengkap ada di
> [Lampiran A](#lampiran-a--evaluasi-migrasi-bahasa-kenapa-ditolak).

---

## Daftar Isi

1. [Ringkasan Eksekutif](#1-ringkasan-eksekutif)
2. [Konteks Ancaman](#2-konteks-ancaman)
3. [Inventaris Lingkungan](#3-inventaris-lingkungan)
4. [Keputusan Versi (ADR Mini)](#4-keputusan-versi-adr-mini)
5. [Prasyarat & Pra-Terbang](#5-prasyarat--pra-terbang)
   - [5.4 Fase 0 — Dependency CVE (dikerjakan lebih dulu)](#54-fase-0--dependency-cve-dikerjakan-lebih-dulu)
6. [Langkah Eksekusi](#6-langkah-eksekusi)
7. [Definition of Done](#7-definition-of-done)
8. [Rollback & Matriks Kegagalan](#8-rollback--matriks-kegagalan)
9. [Hardening Lanjutan (Out of Scope)](#9-hardening-lanjutan-out-of-scope)
10. [Lampiran A — Evaluasi Migrasi Bahasa (Kenapa Ditolak)](#lampiran-a--evaluasi-migrasi-bahasa-kenapa-ditolak)
11. [Lampiran B — Referensi & Sumber](#lampiran-b--referensi--sumber)
12. [Lampiran C — Log Eksekusi](#lampiran-c--log-eksekusi)

---

## 1. Ringkasan Eksekutif

**Masalah.** Mesin ini menjalankan PHP **8.3.30** di bawah Apache `mod_php`. PHP 8.3.30
berada di rentang terdampak dua kerentanan core PHP yang dijuluki **php2shell**
(`CVE-2026-109618`, `CVE-2026-109619`). Keduanya memungkinkan **unauthenticated remote
code execution tanpa bergantung pada kode aplikasi**: apa pun yang berjalan di atas PHP
tersebut perlu diperlakukan sebagai berisiko, termasuk landing page "Kolam Prima".
Tidak ditemukannya pola berbahaya pada pemeriksaan terbatas bukan bukti aplikasi bebas celah.

**Keputusan.** Dua fase, berurutan dengan gate validasi:
1. **Fase 0 (§5.4)** — tangani advisory dependency (catatan audit sebelumnya:
   **13 advisory di 7 paket**, 6 paket produksi). Dikerjakan di PHP 8.3.30 hanya jika
   baseline dan resolver memungkinkan, estimasi awal ±20 menit.
2. **Fase 1 (§6)** — patch runtime ke **PHP 8.5.12** (build `8.5.12-1`, x64 Thread Safe),
   ±30 menit.

**Bukan** migrasi ke bahasa/framework lain. Alasan lengkap di
[Lampiran A](#lampiran-a--evaluasi-migrasi-bahasa-kenapa-ditolak).

**Ruang lingkup.** Fase 0: update dependency terarah; catatan dry-run sebelumnya adalah
21 update, 0 install, 0 removal, **bukan hasil yang dijamin saat eksekusi ulang**. Fase 1:
upgrade runtime dan verifikasi. Tidak ada migrasi skema atau perubahan kode aplikasi yang
direncanakan, tetapi smoke test upload/pengaturan dapat mengubah data lokal. Estimasi total
±45–60 menit untuk proyek ini; validasi empat proyek lain dan penanganan kegagalan menambah waktu.

**Yang tidak diklaim.** Dokumen ini tidak menutup risiko non-runtime (akun admin default,
`APP_DEBUG=true`, upload tanpa batas MIME, stored XSS via SVG). Itu ada di
[§9](#9-hardening-lanjutan-out-of-scope) dan butuh plan terpisah.

**Kriteria "selesai"** (lengkap di [§7](#7-definition-of-done)): audit berhasil diambil dan
0 advisory, CLI/web memakai versi patched target, web SAPI = `apache2handler`, platform
check lolos, semua test baseline lulus (catatan sebelumnya **32 test**), upload/admin normal,
tidak ada deprecation aplikasi baru, dan empat proyek lain tervalidasi. Angka baseline
harus diperbarui jika suite berubah; jangan menghapus test demi menyamai angka ini.

---

## 2. Konteks Ancaman

### 2.1 Fakta CVE

| Item | Nilai |
|---|---|
| Nama publik | php2shell |
| CVE | `CVE-2026-109618` (korupsi saat penanganan out-of-memory) dan `CVE-2026-109619` (stale global state yang bertahan antar-request) |
| Jenis | Memory-safety di **core PHP** (bukan di Laravel/paket aplikasi) |
| Skor | CVSS 9.2 (CRITICAL) |
| Dampak | Unauthenticated RCE, memory disclosure gaya Heartbleed, DoS |
| Status exploitasi | Masuk **CISA KEV Catalog** 26 Okt 2026 (artinya sudah dieksploitasi di lapangan); PoC publik beredar |
| Versi terpatch | **8.2.35**, **8.3.36**, **8.4.27**, **8.5.12** |
| Versi mesin ini | **8.3.30** → **rentan** |
| PHP 8.1 & lebih lama | EOL upstream; vendor/distro dapat menyediakan backport, dan tersedia mitigasi source minimal untuk beberapa versi EOL. Jangan mengasumsikan aman tanpa konfirmasi vendor; utamakan pindah ke cabang didukung |

### 2.2 Mengapa aplikasi yang kodenya bersih tetap terkena

RCE terjadi di dalam interpreter PHP (memory manager, shutdown handler, session globals),
jadi kode PHP apa pun — termasuk landing page satu halaman tanpa form yang menyimpan data —
bisa dipakai sebagai pemicu. Inilah alasan patch runtime lebih penting daripada audit kode
aplikasi untuk kasus ini.

### 2.3 Kondisi exploit (agar mitigasi tidak salah sasaran)

**Jangan gunakan satu daftar prasyarat universal untuk kedua CVE.**

- **CVE-2026-109618 (OOM corruption):** kegagalan alokasi dapat meninggalkan state belum
  valid yang kemudian dikonsumsi saat shutdown request. Pada `mod_php`, jalur ini dapat
  dipicu saat pemrosesan input sebelum kode aplikasi. Tidak mensyaratkan secara universal
  upload progress aktif atau request kedua pada worker yang sama.
- **CVE-2026-109619 (stale global state):** state milik request terdahulu dikonsumsi oleh
  request berikutnya pada worker yang sama sebelum inisialisasi normal. Upload progress
  adalah salah satu jalur producer/consumer; diagnostic INI dan state lain juga relevan.
  Pada PHP 8.5 ada jalur fatal error backtrace tersendiri.
- `session.upload_progress.enabled=0` memutus jalur session yang didemonstrasikan,
  **bukan seluruh jalur kedua CVE**. Patch lengkap tetap diperlukan.

Catatan audit sebelumnya menyebut Apache Windows **MPM WinNT**, bukan `mpm_prefork`.
MPM WinNT memakai parent dan child process ber-thread. Satu child process dengan banyak
thread tidak membuktikan semua thread berbagi request heap/state PHP yang sama pada build
ZTS. `MaxConnectionsPerChild 0` juga tidak membuktikan keberhasilan eksploitasi pada mesin
ini. Reliabilitas eksploit bergantung pada build, extension, frontend, dan allocator.

Rujukan primer: advisory PHP [OOM](https://github.com/php/php-src/security/advisories/GHSA-w8hw-w9fx-j79w)
dan [stale state](https://github.com/php/php-src/security/advisories/GHSA-6wmf-4rv5-p3rp).

### 2.4 Matriks exposure saat ini

| Faktor | Kondisi | Dampak |
|---|---|---|
| Versi PHP | 8.3.30 | Rentan |
| SAPI | Apache `mod_php` | Profil risiko tertinggi |
| `session.upload_progress` | CLI teramati aktif; konfigurasi web perlu diverifikasi | Salah satu jalur berisiko; bukan prasyarat universal |
| Worker recycling | Catatan audit: MPM WinNT, `MaxConnectionsPerChild 0` | Bukan bukti kesamaan state antar-thread atau eksploit berhasil |
| Sudah pernah deploy? | **Belum**, menurut operator | Exposure lebih rendah; binding, firewall, LAN, tunnel, dan port forwarding belum diverifikasi |
| Data sensitif di app | Konten, kredensial, dan secret konfigurasi perlu dilindungi | RCE berjalan dengan hak akun web worker, berpotensi mengakses proyek lain dan file pengguna |
| Dependency CVE | Catatan audit: **13 advisory / 7 paket** (6 produksi) | Jumlah dan reachability perlu diperiksa ulang; lihat §5.4 |

**Kesimpulan:** tidak perlu panik, tapi **tidak boleh menunggu deploy**. Patch runtime
sekarang, sebelum Fase 10, supaya tidak pernah ada jendela waktu di mana versi rentan
menghadap internet. Dependency CVE ditutup lebih dulu karena murah dan bisa divalidasi
terhadap baseline runtime yang sama setelah test awal dikonfirmasi lulus.

### 2.5 Mengapa bukan migrasi bahasa

Pertanyaan awal operator adalah "migrasi Laravel ke bahasa lain". Itu diuji dan ditolak —
alasan lengkap di [Lampiran A](#lampiran-a--evaluasi-migrasi-bahasa-kenapa-ditolak).
Ringkas: PHP2Shell ada di **engine PHP**, bukan di kode aplikasi. Pemeriksaan terbatas sebelumnya mencatat nol
temuan sink RCE (tidak ada `eval`/`exec`/`unserialize`/include dinamis/raw SQL), dan itu
**tidak menolong** — bug terjadi sebelum kode aplikasi berjalan. Sebaliknya, Go/Node/Python/
JVM semuanya punya riwayat CVE runtime sendiri; migrasi memindahkan kewajiban patch,
tidak menghapusnya, dengan biaya 3–15 hari kerja (~70% untuk membangun ulang panel admin
Filament yang sekarang gratis).

---

## 3. Inventaris Lingkungan

Inventaris berikut berasal dari catatan audit sebelumnya, bukan jaminan kondisi mesin saat
eksekusi. Peninjauan read-only terbaru mengonfirmasi CLI 8.3.30, Laravel 13.35.0,
Filament 5.10.1, dan platform mismatch `symfony/yaml`. Versi Apache/Composer, konfigurasi
web, proyek lain, serta baseline test perlu diverifikasi ulang sebelum dipakai sebagai gate.

| Komponen | Nilai terverifikasi |
|---|---|
| Folder PHP | `D:\laragon\bin\php\php-8.3.30-Win32-vs16-x64` (satu-satunya versi terpasang) |
| SAPI | Apache `mod_php` via `php8apache2_4.dll` |
| Apache | `D:\laragon\bin\apache\httpd-2.4.66-260223-Win64-VS18`, build Apache Lounge VS18 |
| MPM | **`WinNT`** (parent + child process threaded; `ThreadsPerChild 150`, `MaxConnectionsPerChild 0`). Diverifikasi lewat `httpd.exe -V` → `Server MPM: WinNT`. Catatan: `conf/extra/httpd-mpm.conf` **tidak di-include** (`#Include` dikomentari di `httpd.conf:498`), jadi blok `mpm_prefork`/`mpm_winnt` di file itu tidak berlaku |
| Wiring PHP | `D:\laragon\etc\apache2\mod_php.conf` — **auto-generated**, isi: 2× `LoadFile` (`libcrypto-3-x64.dll`, `libssl-3-x64.dll`), `LoadModule php_module ...\php8apache2_4.dll`, `PHPIniDir` — semuanya path absolut |
| `php.ini` | `D:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.ini` (1664 baris) |
| `extension_dir` | baris 667 — **satu-satunya baris berisi nama versi** (`...php-8.3.30-Win32-vs16-x64/ext`) |
| `error_log` | baris 515 — `D:/laragon/tmp/php_errors.log` (tidak ber-versi) |
| `session.save_path` | baris 1180 — `D:/laragon/tmp` (tidak ber-versi) |
| `include_path` | baris 647 — `.;D:/laragon/etc/php/pear` (tidak ber-versi) |
| `sendmail_path` | baris 962 — `D:/laragon/bin/mailpit/1.22.3/mailpit.exe sendmail` (tidak ber-versi) |
| `curl.cainfo` | baris 1638 — `D:\laragon\etc\ssl\cacert.pem` (tidak ber-versi) |
| `memory_limit` / `max_execution_time` | `512M` / `36000` |
| `upload_max_filesize` / `post_max_size` | `2G` / `2G` (batas nyata di form Filament: 2–50 MB) |
| `expose_php` | `On` baris 343 (membocorkan versi PHP di response header) |
| `session.upload_progress.enabled` | baris 1325 dikomentari → **default `On`** |
| `report_memleaks` | `On` baris 472 — **deprecated di PHP 8.5**, harus dihapus saat migrasi |
| `zend_extension=opcache` | baris 833 dikomentari (tidak aktif) |
| Extension aktif (14) | `curl, fileinfo, gd, intl, mbstring, exif, mysqli, openssl, pdo_mysql, pdo_sqlite, sodium, sqlite3, xsl, zip` |
| MySQL | 8.4.x, DB `website_kolam`, user `root` tanpa password (lokal) |
| Aplikasi | Laravel **13.35.0**, Filament **5.10.1** (terkonfirmasi); catatan frontend: Tailwind 4, Alpine 3 |
| Composer | **2.6.6** (build Des 2023) — terlalu tua untuk PHP 8.5, wajib di-update ke 2.11.x |
| VC++ Redistributable 2015–2022 x64 | **Terpasang**, v14.51.36247.00 (syarat build VS17) |
| Project lain di `D:\laragon\www` | `klinik-kasir`, `sistem-pembayaran-klinik`, `web-igrgto`, `yayasan-bhakti-abu-rohana` — **semua berbagi runtime PHP ini** |
| Test | 5 file Feature (AdminAuth, AdminCrud, AdminSmoke, HomePage, WhatsAppHelper), **32 test / 130 assertions** (baseline 13 Nov 2026) |

### 3.1 Blast radius: empat project lain

Karena satu runtime dipakai bersama, upgrade PHP menyentuh semua project di `D:\laragon\www`.
Catatan audit `composer.lock` tidak menemukan penolakan constraint PHP 8.5; ini
**bukan bukti kompatibilitas perilaku aplikasi**:

| Project | Framework | Constraint `php` | Blocker 8.5? |
|---|---|---|---|
| `website-kolam` | Laravel 13.35 | `^8.3` | Tidak |
| `klinik-kasir` | Laravel 13.5 | `^8.3` | Tidak |
| `sistem-pembayaran-klinik` | Laravel 13.9 | `^8.3` | Tidak |
| `web-igrgto` | Laravel 13.2 | `^8.3` | Tidak |
| `yayasan-bhakti-abu-rohana` | Laravel 12.58 | `^8.2` | Tidak |

Kebijakan resmi Laravel: **Laravel 13 → PHP 8.3–8.5**, **Laravel 12 → PHP 8.2–8.5**.
Tidak ada satu pun paket di kelima lock file yang menetapkan batas atas ≤ 8.4.

**Konsekuensi operasional:** setelah switch, keempat project lain ikut berjalan di 8.5.12.
Sebelum switch, inventarisasi requirement, extension, baseline test, dan cara smoke test
masing-masing proyek. Setelah switch, **wajib** jalankan platform check, test yang tersedia,
dan smoke test jalur utama pada keempat proyek itu. Jika ada proyek tanpa test, catat
keterbatasannya dan hasil verifikasi manual. Jangan menyatakan upgrade runtime bersama
selesai hanya berdasarkan `website-kolam`; bila belum bisa divalidasi, tunda switch global
atau rencanakan runtime terpisah terlebih dahulu.

### 3.2 Dependency landscape terhadap PHP 8.5

`composer.lock` diperiksa untuk memastikan tidak ada paket yang mengunci runtime di ≤ 8.4:

| Constraint `php` di `composer.lock` | Paket (contoh) | Menerima 8.5? |
|---|---|---|
| `^8.2\|^8.3\|^8.4\|^8.5` | paket inti Filament | Ya |
| `~8.3.0 \|\| ~8.4.0 \|\| ~8.5.0` | `openspout/openspout` v4.32.0 | Ya |
| `8.1 - 8.5`, `8.2 - 8.5` | paket pendukung | Ya |
| `>=8.4` | `symfony/yaml` v8.0.15 (dev-only) | Ya untuk 8.5 — **tapi menolak 8.3**, lihat catatan |
| `^8.0`, `^8.1`, `^8.2` | mayoritas paket | Ya (open-ended ke atas) |

Kebijakan resmi: **Laravel 13 mendukung PHP 8.3–8.5** dan sudah bergantung pada
`symfony/polyfill-php85`.

> **Temuan penting (13 Nov 2026):** `symfony/yaml v8.0.15` menuntut `php >=8.4`, sementara
> mesin ini menjalankan 8.3.30. Akibatnya **`composer check-platform-reqs` sudah GAGAL
> hari ini** (bukan nanti setelah upgrade). Paket ini dev-only (via
> `laravel/boost → laravel/roster`) sehingga tidak memengaruhi produksi, tapi harus
> diperbaiki — lihat §5.4, di mana ia di-downgrade ke `v7.4.23`.

### 3.3 Kesiapan kode aplikasi terhadap deprecation PHP 8.5

Pencarian di `app/` untuk pola yang dideprecate di 8.5: **tidak ada temuan**.

- Tidak ada cast non-kanonik `(boolean)`, `(integer)`, `(double)`, `(binary)`.
- Tidak ada operator backtick sebagai alias `shell_exec()`.
- Tidak ada `case ...;` (titik koma setelah `case`) di `switch`.

Risiko deprecation 8.5 yang tersisa berasal dari paket pihak ketiga, dan itu akan terlihat
sebagai baris `Deprecated:` di log — bukan error fatal.

---

## 4. Keputusan Versi (ADR Mini)

### 4.1 Opsi

| Versi | Status dukungan (per Nov 2026) | Risiko upgrade | Catatan |
|---|---|---|---|
| 8.3.36 | Security-only sejak **31 Des 2025**; EOL **31 Des 2027** | Paling rendah (patch sekelas) | Menutup CVE, tapi tidak menambah umur |
| 8.4.27 | Bug fix berakhir **31 Des 2026**; security s/d **31 Des 2028** | Rendah–sedang | Ekosistem paling teruji |
| **8.5.12** | Aktif s/d **31 Des 2027**; security s/d **31 Des 2029** | Sedang (versi terbaru) | **Dipilih** |

### 4.2 Keputusan

**PHP 8.5.12 (build `8.5.12-1`, VS17, x64, Thread Safe).** Alasan:

1. Menutup php2shell (8.5.12 termasuk versi terpatch resmi).
2. Umur dukungan paling panjang → memberi waktu lebih lama sebelum upgrade cabang berikutnya;
   patch keamanan rutin tetap wajib.
3. Dependency sudah menyatakan dukungan 8.5 (§3.2), Laravel 13 mendukung 8.3–8.5.
4. Kode aplikasi bersih dari deprecation 8.5 (§3.3).

### 4.3 Konsekuensi yang diterima

- Perlu memeriksa `Deprecated:` di log setelah upgrade; perbaikan deprecation pihak ketiga
  di luar kendali kita (tunggu rilis paket).
- Build `VS17` mewajibkan **Visual C++ Redistributable 2015–2022 x64** terpasang — sudah
  terverifikasi ada (v14.51.36247.00, §3).
- `report_memleaks=On` di `php.ini` lama akan memicu deprecation di 8.5 → dihapus di §6 Langkah 4.
- Composer 2.6.6 harus di-update ke 2.11.x (§6 Langkah 3b) sebelum dipakai dengan PHP 8.5.
- Titik naik versi berikutnya: **PHP 8.6** (GA 19 Nov 2026, tapi untuk mesin ini tunggu
  sampai ekosistem stabil — jadwal realistis ±2027). Sebelum itu, patch keamanan 8.5.x tetap
  harus dipasang (rilis rutin ±4 minggu, security release bisa mendadak). **8.5.13** sudah
  di-tag sebagai RC1 (3–4 Nov 2026) dan akan menutup `CVE-2026-115560` — jadwalkan patch
  kecil begitu stable-nya rilis.
- Keempat project Laravel lain di `D:\laragon\www` ikut pindah ke 8.5.12 (§3.1).

### 4.4 Alternatif yang ditolak

- **Migrasi bahasa (Django / Next.js / AdonisJS / Go).** Biaya 3–15 hari kerja, ~70% di
  antaranya untuk membangun ulang panel admin yang di Filament sudah gratis, dan tidak
  menghapus kewajiban patch runtime. Detail: [Lampiran A](#lampiran-a--evaluasi-migrasi-bahasa-kenapa-ditolak).
- **Tetap di 8.3.30 + mitigasi konfigurasi saja.** Ditolak: mitigasi bersifat parsial
  (lihat §2.3), dan CISA menempatkan patch sebagai tindakan wajib.
- **Naik ke cabang 8.4 yang sudah patched.** Alternatif valid dengan risiko transisi lebih
  rendah. Berakhirnya active support pada 31 Des 2026 **bukan EOL**; security support tetap
  sampai 31 Des 2028. Tidak dipilih karena preferensi umur dukungan 8.5, bukan karena 8.4
  harus segera ditinggalkan. Jika validasi salah satu proyek gagal di 8.5, evaluasi 8.4
  atau patch cabang 8.3 sebagai tahap sementara.

---

## 5. Prasyarat & Pra-Terbang

### 5.1 Yang dibutuhkan

- [ ] Akses tulis ke `D:\laragon` (folder PHP, `etc\apache2`, `tmp`).
- [ ] **PowerShell** di lingkungan Laragon dan terminal baru untuk verifikasi PATH.
- [ ] Pastikan binding Apache, firewall, tunnel, dan port forwarding tidak mengekspos runtime
      rentan ke jaringan yang tidak dipercaya selama pekerjaan.
- [ ] Tetapkan versi target patched terbaru yang tersedia saat eksekusi. `8.5.12` di dokumen
      ini adalah target bertanggal, bukan perintah untuk mengabaikan patch yang lebih baru.
      Jika target berubah, perbarui nama ZIP, folder, checksum, dan ekspektasi secara konsisten.
- [ ] `mysqldump` tersedia (`D:\laragon\bin\mysql\...\bin`).
- [ ] 7-Zip atau Explorer (ekstrak ZIP).
- [ ] Browser untuk verifikasi web.
- [ ] Koneksi internet ke `windows.php.net` (unduhan ±34 MB).
- [ ] **Visual C++ Redistributable 2015–2022 x64** terpasang — syarat build `VS17`.
      Cek via PowerShell:
      ```powershell
      Get-ItemProperty 'HKLM:\SOFTWARE\Microsoft\VisualStudio\14.0\VC\Runtimes\x64' |
        Select-Object Installed, Version
      ```
      Jika `Installed` bukan `1`, pasang dari halaman resmi Microsoft sebelum langkah 3.

### 5.2 Berkas yang harus di-backup (langkah 1)

- `D:\laragon\etc\apache2\mod_php.conf`
- `D:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.ini`
- Dump DB `website_kolam`
- `composer.json` dan `composer.lock` **sebelum Fase 0** dan **setelah Fase 0**
- Working tree git bersih, atau perubahan operator sudah diamankan secara eksplisit;
  jangan melakukan commit, stash, atau reset otomatis

### 5.3 Estimasi waktu

| Fase | Estimasi |
|---|---|
| **Fase 0 — dependency CVE (§5.4)** | **20 menit** |
| Backup + pra-terbang | 5 menit |
| Unduh + ekstrak | 5–10 menit |
| `php.ini` baru + hardening | 5 menit |
| Switch versi + audit wiring + restart | 5 menit |
| Verifikasi CLI/web/app/test | 10 menit |
| **Total Fase 1** | **25–40 menit** |
| **Total keseluruhan** | **±45–60 menit** |
| Rollback | Estimasi awal 2 menit untuk switch; pemulihan dependency/data dan validasi bisa lebih lama |

---

### 5.4 Fase 0 — Dependency CVE (dikerjakan lebih dulu)

**Mengapa ini ada.** `composer audit` pada 13 Nov 2026 menemukan **13 advisory di 7 paket**,
6 di antaranya paket produksi. Pemeriksaan terbatas sebelumnya tidak menemukan penggunaan
beberapa fitur terkait advisory (`#[Authorize]`, RichEditor, YAML, pengiriman email).
**Ini bukan bukti semua advisory tidak reachable.** Login, endpoint Livewire/framework,
header, cookie, query, dan body tetap merupakan input HTTP walaupun controller publik
hanya menyediakan route GET read-only. Baca kondisi tiap advisory dan audit ulang saat
eksekusi. Temuan platform yang sudah dikonfirmasi:

- `symfony/yaml v8.0.15` **sudah menggagalkan `composer check-platform-reqs`** hari ini
  karena menuntut `php >=8.4` sementara mesin 8.3.30.
- Biaya menutupnya rendah (21 update, tanpa install/removal) dan mencegah regresi saat Fase 10.

**Mengapa sebelum Fase 1.** Memisahkan perubahan dependency dan runtime memudahkan isolasi
regresi. Catatan baseline adalah 32 test hijau di 8.3.30, tetapi harus direkam ulang.
Platform saat ini tidak sepenuhnya valid karena `symfony/yaml`; jika baseline test gagal,
berhenti dan investigasi terlebih dahulu. Jangan memakai `--ignore-platform-reqs` untuk
memaksakan instalasi atau menganggap baseline gagal sebagai dampak update.

#### Tabel target

Snapshot rekomendasi audit sebelumnya, bukan advisory/version matrix yang diverifikasi
ulang oleh revisi dokumen ini. Saat eksekusi, cocokkan `composer audit` terbaru, kondisi
advisory, versi patched, dan hasil resolver; jangan mengejar angka 13 bila feed berubah.

| Paket | Sekarang | Target | CVE / advisory utama |
|---|---|---|---|
| `livewire/livewire` | v4.4.7 | **v4.4.8** | PKSA-xhv3-nr94-h8db — authorization bypass `#[Authorize]` |
| `symfony/http-foundation` | v7.4.20 | **v7.4.23** | CVE-2026-118514 + PKSA-pr6q / nxjs / b2gg |
| `symfony/http-kernel` | v7.4.20 | **v7.4.23** | CVE-2026-123339 — inline fragment host poisoning |
| `symfony/routing` | v7.4.20 | **v7.4.23** | CVE-2026-118516 — host placeholder bypass |
| `symfony/mime` | v7.4.19 | **v7.4.22** | CVE-2026-45070 — email header injection |
| `symfony/html-sanitizer` | v7.4.20 | **v7.4.23** | CVE-2026-121949 / 121373 / 118517 |
| `symfony/yaml` | v8.0.15 | **v7.4.23** (downgrade) | CVE-2026-123792, CVE-2026-109413 + memperbaiki platform check |

Catatan `symfony/yaml`: dev-only via `laravel/boost → laravel/roster`. Downgrade ke 7.4
bukan jalan mundur — Symfony **7.4 adalah LTS** (bug fix s/d Nov 2028, security s/d Nov 2029),
sedangkan 8.0 sudah **unmaintained** (EOL Jul 2026). Justru ini upgrade jalur dukungan.

#### Langkah

Jalankan blok berikut **bertahap**, bukan sekaligus tanpa membaca output. Perintah native
yang gagal tidak otomatis menghentikan PowerShell; periksa exit code dan output setiap gate.

```powershell
Set-Location 'D:\laragon\www\website-kolam'

# 0a. Gate awal: lihat perubahan operator sebelum menyentuh dependency
git --no-pager --no-optional-locks status --short
# Jika ada perubahan yang belum diamankan, BERHENTI dan sepakati penanganannya.

# 0b. Checkpoint immutable: jangan timpa backup dari percobaan sebelumnya
$checkpoint = 'D:\laragon\backup\website-kolam-pre-security-update'
if (Test-Path $checkpoint) {
    throw 'Checkpoint sudah ada. Pilih lokasi baru dan catat; jangan timpa backup lama.'
}
New-Item -ItemType Directory -Path $checkpoint -Force | Out-Null
Copy-Item -Path composer.json, composer.lock -Destination $checkpoint
# Untuk rollback lingkungan identik, sepakati ruang disk lalu backup vendor sekarang.
# Platform awal tidak konsisten; composer install dari lock awal bisa gagal di PHP 8.3.
# Jika pemulihan identik menjadi syarat dan backup vendor belum tersedia, jangan lanjut.
php -v
composer --version
composer check-platform-reqs
# Kegagalan yaml >=8.4 adalah baseline yang diketahui; kegagalan lain perlu ditinjau.
composer audit --format=json | Out-File "$checkpoint\audit-before.json" -Encoding utf8
# Exit nonzero audit dapat berarti advisory ditemukan; bedakan dengan kegagalan jaringan.
php artisan test --compact
if ($LASTEXITCODE -ne 0) { throw 'Baseline test gagal. Jangan lanjut update.' }

# 0c. Dry-run: constraint bertanggal, hasil resolver harus ditinjau ulang
$targets = @(
    'livewire/livewire:^4.4.8'
    'symfony/http-foundation:^7.4.23'
    'symfony/http-kernel:^7.4.23'
    'symfony/routing:^7.4.23'
    'symfony/mime:^7.4.22'
    'symfony/html-sanitizer:^7.4.23'
    'symfony/yaml:^7.4.23'
)
composer update @targets --with-dependencies --dry-run
if ($LASTEXITCODE -ne 0) { throw 'Dry-run gagal. Jangan lanjut instalasi.' }
```

**Gate persetujuan:** periksa daftar perubahan aktual. Catatan lama adalah 21 update,
0 install, 0 removal, tetapi constraint `^` dapat memilih patch/minor yang lebih baru.
Jika cakupan berubah, ada paket mayor baru, atau framework ikut berubah, berhenti dan
review hasil resolver. Jangan menganggap hasil dry-run lama masih berlaku.

Setelah dry-run disetujui, di sesi PowerShell yang sama:

```powershell
composer update @targets --with-dependencies
if ($LASTEXITCODE -ne 0) { throw 'Update gagal. Periksa kondisi file dan vendor sebelum lanjut.' }
composer audit
if ($LASTEXITCODE -ne 0) { throw 'Audit belum bersih atau gagal diambil. Jangan lanjut Fase 1.' }
composer check-platform-reqs
if ($LASTEXITCODE -ne 0) { throw 'Platform belum sesuai. Jangan lanjut Fase 1.' }
php artisan test --compact
if ($LASTEXITCODE -ne 0) { throw 'Test setelah update gagal. Investigasi atau rollback Fase 0.' }
git --no-pager diff -- composer.json composer.lock
git --no-pager --no-optional-locks status --short
```

Composer scripts pada proyek ini dapat mem-publish asset setelah update. Tinjau seluruh
perubahan file, bukan hanya lock: perubahan asset hasil script harus dijelaskan dan
disetujui, bukan dibuang atau diterima otomatis.

**Ekspektasi:**
- `composer audit` → **0 advisory** (dari 13).
- `composer check-platform-reqs` → semua `success` (sebelumnya GAGAL di `symfony/yaml`).
- `php artisan test --compact` → **32 passed**.
- `laravel/framework` **tidak** ikut berubah (bukan target; kalau ikut naik, berarti
  `--with-dependencies` menariknya — hentikan dan periksa).

**Jika gagal:**
- `Your requirements could not be resolved` → baca paket/constraint yang menolak dan gunakan
  `composer prohibits php 8.5.12` jika menilai target runtime. Jangan memasang paket dengan
  mengabaikan platform. Pilih versi patched kompatibel atau tinjau ulang urutan fase.
- Test merah → bandingkan dengan baseline, catat paket penyebab, lalu gunakan rollback
  checkpoint Fase 0 di §8.1 jika diperlukan.
- Jangan memakai `git checkout`/`git restore` untuk rollback tanpa memastikan sumbernya
  benar-benar checkpoint pra-update; HEAD belum tentu sama dengan kondisi awal operator.

**Yang TIDAK dikerjakan di fase ini:** `laravel/framework` v13.35 → v13.42, `filament`
v5.10.1 → v5.11.2, `phpunit` 12.5.38 → 12.5.43. Semua itu update fitur, bukan CVE.
Jadwalkan terpisah supaya perubahan CVE tetap kecil dan mudah di-review.

---

## 6. Langkah Eksekusi

> Konvensi: setiap langkah = **Tujuan / Perintah / Ekspektasi / Jika gagal**.
> Jangan lompat langkah. Langkah 6 dan 9 adalah dua titik yang paling sering dilewatkan,
> dan melewatkannya membuat semua verifikasi di bawahnya **lolos secara palsu**.
> **Prasyarat: §5.4 (Fase 0) sudah selesai dan hijau.**

### Langkah 1 — Rollback anchor

**Tujuan:** membuat checkpoint **pasca-Fase 0 / pra-Fase 1**, terpisah dari checkpoint
pra-update dependency. Jangan menimpa checkpoint Fase 0.

```powershell
# 1a. Simpan metadata dependency yang sudah lolos Fase 0
Set-Location 'D:\laragon\www\website-kolam'
$runtimeCheckpoint = 'D:\laragon\backup\website-kolam-pre-runtime-upgrade'
if (Test-Path $runtimeCheckpoint) {
    throw 'Checkpoint runtime sudah ada. Pilih lokasi baru dan catat sebelum melanjutkan.'
}
New-Item -ItemType Directory -Path $runtimeCheckpoint -Force | Out-Null
Copy-Item -Path composer.json, composer.lock -Destination $runtimeCheckpoint

# Backup konfigurasi; jangan timpa anchor dari percobaan sebelumnya
if ((Test-Path 'D:\laragon\etc\apache2\mod_php.conf.bak-8.3.30') -or
    (Test-Path 'D:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.ini.bak-8.3.30')) {
    throw 'Backup konfigurasi sudah ada. Tinjau dan pilih nama anchor baru sebelum lanjut.'
}
Copy-Item 'D:\laragon\etc\apache2\mod_php.conf' 'D:\laragon\etc\apache2\mod_php.conf.bak-8.3.30'
Copy-Item 'D:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.ini' 'D:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.ini.bak-8.3.30'

# 1b. Backup database (jaga-jaga; upgrade ini sebenarnya tidak menyentuh data)
New-Item -ItemType Directory -Force 'D:\laragon\backup' | Out-Null
# Konfirmasi lokasi mysqldump sesuai versi MySQL terpasang sebelum menjalankan.
& 'D:\laragon\bin\mysql\mysql-8.4.3-winx64\bin\mysqldump.exe' -u root --single-transaction --default-character-set=utf8mb4 --result-file='D:\laragon\backup\website-kolam-pre-runtime-upgrade\website_kolam.sql' website_kolam
if ($LASTEXITCODE -ne 0) { throw 'Backup database gagal. Jangan lanjut.' }
# --result-file menghindari perubahan encoding SQL oleh pipeline Windows PowerShell.

# Simpan file upload sebelum smoke test yang dapat mengubah konten
Copy-Item 'storage\app\public' "$runtimeCheckpoint\public-uploads" -Recurse

# 1c. Pastikan working tree bersih
cd D:\laragon\www\website-kolam
git --no-pager --no-optional-locks status --short
```

**Ekspektasi:** backup metadata dependency, konfigurasi, upload, dan SQL tersedia. Dump
berukuran > 0 byte saja bukan bukti dapat di-restore: periksa exit code, isi/encoding, dan
bila memungkinkan uji restore ke database sementara yang disepakati operator.
Perubahan Fase 0 pada lock file boleh masih terlihat; perubahan operator lain harus sudah
diamankan sebelum lanjut. Tidak ada commit atau stash otomatis.
**Jika gagal:** jangan lanjut. Tanpa backup, rollback jadi menebak-nebak.
**Jangan** menghapus folder `php-8.3.30-Win32-vs16-x64`; itu bagian dari rencana rollback.

---

### Langkah 2 — Unduh & verifikasi build

**Tujuan:** mendapat binary resmi dengan varian yang benar.

**Varian yang wajib:** x64 **Thread Safe (TS)**. `mod_php` tidak bisa memuat build NTS.

- Halaman resmi: `https://downloads.php.net/~windows/releases/`
  (`windows.php.net/downloads/releases/` melakukan 302 ke sini; keduanya resmi)
- Berkas target: **`php-8.5.12-1-Win32-vs17-x64.zip`** (36.453.069 byte / ±35 MB,
  build 26 Okt 2026). Tersedia dan terverifikasi bisa diunduh pada 13 Nov 2026.
- **Jangan** mengambil dari portal download pihak ketiga.

Verifikasi checksum:

```powershell
Get-FileHash "$env:USERPROFILE\Downloads\php-8.5.12-1-Win32-vs17-x64.zip" -Algorithm SHA256
```

Bandingkan dengan `sha256sum.txt` resmi di halaman yang sama. Nilai resmi **sudah diambil**
pada audit sebelumnya. **Verifikasi ulang dari sumber resmi saat eksekusi**, lalu bandingkan
hasil `Get-FileHash`. Hash di dokumen ini belum diverifikasi ulang dalam revisi runbook:

| Berkas | SHA256 resmi (dari `sha256sum.txt`) | SHA256 file lokal | Cocok? |
|---|---|---|---|
| `php-8.5.12-1-Win32-vs17-x64.zip` | `0dacd6484b092b5e9ae9fa3d7f96a937caf9d874eee2d980b68ed3129741144a` | | |

> Kalau hash tidak cocok: hapus file, unduh ulang. **Jangan** "tetap lanjut karena mungkin
> salah ketik" — file yang salah hash bisa berarti unduhan korup atau di-tamper.

> **Jangan tertukar:** `php-8.5.12-1-nts-Win32-vs17-x64.zip` (NTS, hash `2a36a2ed...`)
> **tidak bisa** dipakai dengan `mod_php`. Yang dibutuhkan TS.

**Jika gagal:** checksum tidak cocok → hapus file, unduh ulang dari halaman resmi.

---

### Langkah 3 — Pasang folder PHP baru

**Tujuan:** menambah versi baru **tanpa** menyentuh yang lama.

```powershell
# 3a. Hentikan semua service Laragon
#     Manual: tray icon Laragon > Stop All
#     (tidak ada perintah CLI resmi; pastikan httpd.exe & mysqld.exe berhenti)

# 3b. Ekstrak (pastikan TIDAK ada folder bersarang)
Expand-Archive "$env:USERPROFILE\Downloads\php-8.5.12-1-Win32-vs17-x64.zip" `
  -DestinationPath 'D:\laragon\bin\php\php-8.5.12-Win32-vs17-x64'
```

**Ekspektasi** — semua ini berada langsung di `D:\laragon\bin\php\php-8.5.12-Win32-vs17-x64\`:

```powershell
Get-ChildItem 'D:\laragon\bin\php\php-8.5.12-Win32-vs17-x64' |
  Where-Object Name -in @('php.exe','php8apache2_4.dll','php8ts.dll','ext','php.ini-development') |
  Select-Object Name
```

Keempat/kelima entri harus muncul. Kalau hasil ekstrak jadi
`...\php-8.5.12-Win32-vs17-x64\php-8.5.12-Win32-vs17-x64\php.exe`, pindahkan isinya satu
level ke atas.

**Alternatif lebih cepat:** `Menu > Tools > Quick add > PHP 8.5` (Laragon mengunduh dan
mendaftarkan sendiri). Kalau memakai jalur ini, **tetap** lanjut ke langkah 4 dan 6 —
Laragon tidak memigrasikan `php.ini` custom-mu secara otomatis.

**Jika gagal:** folder tidak muncul di `Menu > PHP > Version` → tutup dan buka ulang
`laragon.exe`.

---

### Langkah 3b — Update Composer (jangan dilewatkan)

**Tujuan:** Composer 2.6.6 (build Des 2023) belum menangani deprecation PHP 8.5.
Tanpa pembaruan, Composer lama dapat mengeluarkan deprecation atau menemui masalah
kompatibilitas. Jangan menyimpulkan ada salah pembacaan constraint tanpa bukti.

Versi minimum aman: **2.8.11** (rilis pertama yang menyertakan fix deprecation 8.5).
Versi stable terbaru per 13 Nov 2026: **2.11.2**.

```powershell
# 3b. Konfirmasi executable yang aktif dan simpan versi awal
Get-Command composer -All
composer --version

# Jalankan hanya setelah wrapper/path composer.phar aktif dipastikan.
# Simpan backup .phar aktif ke checkpoint runtime sebelum self-update.
Copy-Item 'C:\ProgramData\ComposerSetup\bin\composer.phar' 'D:\laragon\backup\website-kolam-pre-runtime-upgrade\composer.phar'
composer self-update --stable
if ($LASTEXITCODE -ne 0) { throw 'Update Composer gagal. Periksa output sebelum lanjut.' }
composer --version
```

**Ekspektasi:** `Composer version 2.11.2 ...` (atau versi 2.8.11+ apa pun).

**Jika gagal:** `Access denied` → konfirmasi path aktif dan izin file terlebih dahulu;
jangan menjalankan seluruh runbook sebagai Administrator. Jika instalasi global memang
membutuhkan elevasi, batasi elevasi pada update Composer. Bila memakai installer resmi
sebagai alternatif, ikuti prosedur verifikasi SHA-384 installer dari getcomposer.org sebelum
menjalankannya; jangan mengunduh lalu mengeksekusi installer tanpa verifikasi.

> Lokasi `composer.phar` di mesin ini terverifikasi: `C:\ProgramData\ComposerSetup\bin\composer.phar`
> (dipanggil oleh `composer.bat` di folder yang sama) menurut audit sebelumnya. Ada juga
> salinan di `D:\laragon\bin\composer\composer.phar`. Konfirmasi ulang wrapper yang aktif
> di terminal biasa dan Laragon; backup/update masing-masing salinan hanya jika dipakai.
> `composer self-update --rollback` hanya berlaku pada PHAR yang di-update dan masih
> menyimpan backup; rollback Composer terpisah dari rollback dependency/runtime.

---

### Langkah 4 — Buat `php.ini` baru (jebakan utama)

**Tujuan:** mempertahankan semua setting Laragon, sambil membetulkan **path absolut yang
mengandung nama versi**.

```powershell
$old = 'D:\laragon\bin\php\php-8.3.30-Win32-vs16-x64'
$new = 'D:\laragon\bin\php\php-8.5.12-Win32-vs17-x64'

# 4a. Copy php.ini lama -> baru (JANGAN pakai php.ini-development)
Copy-Item "$old\php.ini" "$new\php.ini"

# 4b. Ganti path lama, tulis UTF-8 tanpa BOM (konsisten di PowerShell 5.1/7)
# Jika file lama berisi nilai non-ASCII, konfirmasi encoding sumber sebelum membaca.
$iniText = (Get-Content "$new\php.ini" -Raw -Encoding utf8) `
    -replace [regex]::Escape('php-8.3.30-Win32-vs16-x64'), 'php-8.5.12-Win32-vs17-x64'
[System.IO.File]::WriteAllText("$new\php.ini", $iniText, [System.Text.UTF8Encoding]::new($false))

# 4c. Cari teks literal, bukan regex yang di-escape lalu diperlakukan literal
Select-String -Path "$new\php.ini" -Pattern 'php-8.3.30' -SimpleMatch
```

**Ekspektasi:** perintah 4c tidak menghasilkan output.

> **Fakta terverifikasi (13 Nov 2026):** di `php.ini` mesin ini hanya **satu** baris yang
> mengandung nama versi, yaitu `extension_dir` (baris 667). Semua path lain sudah bersih
> dari nama versi, jadi penggantian di 4b hanya menyentuh baris itu.

Baris yang wajib benar:

```ini
extension_dir =  "D:/laragon/bin/php/php-8.5.12-Win32-vs17-x64/ext"
```

Baris berikut **tidak** mengandung nama versi → biarkan apa adanya (nomor baris
terverifikasi di `php.ini` 8.3.30):

```ini
error_log="D:/laragon/tmp/php_errors.log"          ; baris 515
include_path=".;D:/laragon/etc/php/pear"           ; baris 647
sendmail_path="D:/laragon/bin/mailpit/1.22.3/mailpit.exe sendmail"  ; baris 962
session.save_path="D:/laragon/tmp"                 ; baris 1180
curl.cainfo="D:\laragon\etc\ssl\cacert.pem"        ; baris 1638
memory_limit=512M                                  ; baris 371
max_execution_time=36000                           ; baris 350
upload_max_filesize=2G                             ; baris 737
post_max_size=2G                                   ; baris 610
```

Verifikasi 14 extension aktif **dan** file `.dll`-nya benar-benar ada:

```powershell
$exts = 'curl','fileinfo','gd','intl','mbstring','exif','mysqli','openssl',
        'pdo_mysql','pdo_sqlite','sodium','sqlite3','xsl','zip'
$ini  = Get-Content "$new\php.ini"
foreach ($e in $exts) {
  $aktif = ($ini -match "^extension=$e$").Count -gt 0
  $dll   = Test-Path "$new\ext\php_$e.dll"
  "{0,-12} aktif={1,-5} dll={2}" -f $e, $aktif, $dll
}
```

**Ekspektasi:** semua `aktif=True` dan `dll=True`.
Catatan: extension ZIP memakai `php_zip.dll`; zlib bukan pengganti ZIP. Pemeriksaan
teks ini belum membuktikan extension berhasil dimuat; pastikan lewat `php -m` dan SAPI web.
Bandingkan juga dengan template konfigurasi serta panduan migrasi 8.3 → 8.4 → 8.5 untuk
menemukan directive yang dihapus/berubah, bukan hanya tiga pola deprecation di §3.3.

Biarkan `;zend_extension=opcache` tetap dikomentari. Opcache di `mod_php` Windows
historis rawan crash dan tidak diperlukan untuk beban lokal.

**Jika gagal:** `PHP Warning: PHP Startup: Unable to load dynamic library` saat langkah 8
= `extension_dir` masih salah atau `.dll` tidak ada di folder baru.

---

### Langkah 5 — Hardening `php.ini` (defense-in-depth)

**Tujuan:** mengurangi dampak bila ada kerentanan kelas serupa di masa depan, dan menutup
kebocoran informasi versi.

Tambahkan/ubah di `[PHP]` dan section terkait. **Tiga baris diubah, satu baris dihapus:**

```ini
; --- hardening (tambahan lokal, 2026-11-13) ---
expose_php = Off                     ; baris 343, sebelumnya On -> jangan bocorkan versi di header
session.upload_progress.enabled = 0  ; baris 1325 (dikomentari) -> aktifkan dengan nilai 0
fatal_error_backtraces = 0           ; directive BARU di PHP 8.5; tidak ada di php.ini 8.3
```

Dan **hapus** baris ini:

```ini
report_memleaks=On                   ; baris 472 -> DEPRECATED di PHP 8.5, akan memicu warning
```

Catatan penting:

- **Jangan** memakai trik `memory_limit=-1`. Itu memang disebut sebagai mitigasi parsial
  untuk php2shell, tetapi menghapus proteksi out-of-memory — menukar satu risiko dengan
  risiko DoS yang lebih mudah.
- `session.upload_progress.enabled=0` dan `fatal_error_backtraces=0` hanya **lapisan
  tambahan**. Yang menutup CVE adalah versi PHP-nya, bukan dua baris ini.
- `fatal_error_backtraces` **hanya ada di PHP 8.5+**. Kalau menaruhnya di php.ini 8.3.30,
  PHP akan mengabaikannya tanpa error — jadi jangan bingung kalau belum berefek sekarang.
- `session.upload_progress.enabled=0` juga merupakan mitigasi resmi untuk
  **`CVE-2026-115560`** (advisory PHP 23 Okt 2026), yang **belum** diperbaiki di 8.5.12.
  Pertahankan setting ini bahkan setelah patch.
- Setiap perubahan `php.ini` baru berlaku efektif setelah **restart semua worker**
  (`Stop All` → `Start All`). Proses lama tetap memakai konfigurasi lama.

**Ekspektasi:** tiga baris di atas ada di `php.ini`, `report_memleaks` sudah tidak ada, dan
tidak ada `expose_php = On` tersisa.

Verifikasi cepat setelah restart:

```powershell
php -i | Select-String 'expose_php|session.upload_progress.enabled|fatal_error_backtraces'
```

Target output: `expose_php => Off`, `session.upload_progress.enabled => Off`,
`fatal_error_backtraces => Off`.

**Jika gagal:** aplikasi yang bergantung pada session upload progress mungkin kehilangan
indikator progresnya. Kode JS aplikasi tampak memakai Alpine, tetapi itu belum membuktikan
perilaku JavaScript vendor. Verifikasi upload/progress panel Filament secara langsung
sebelum menyimpulkan fitur ini tidak dibutuhkan.

---

### Langkah 6 — Aktifkan versi baru & audit file wiring

**Tujuan:** memastikan Apache benar-benar memuat binary baru. Ini titik gagal paling sunyi:
kalau terlewat, `php -v` di CLI bisa sudah 8.5.12 sementara **web masih 8.3.30 yang rentan**.

```powershell
# 6a. Pilih versi di UI
#     Manual: tray Laragon > PHP > Version > 8.5.12

# 6b. Audit hasil tulis-ulang otomatis Laragon
Get-Content 'D:\laragon\etc\apache2\mod_php.conf'
```

**Ekspektasi** — keempat baris menunjuk folder baru:

```apache
# This file is auto-generated, so please keep it intact.
LoadFile "D:/laragon/bin/php/php-8.5.12-Win32-vs17-x64/libcrypto-3-x64.dll"
LoadFile "D:/laragon/bin/php/php-8.5.12-Win32-vs17-x64/libssl-3-x64.dll"
LoadModule php_module "D:/laragon/bin/php/php-8.5.12-Win32-vs17-x64/php8apache2_4.dll"
PHPIniDir "D:/laragon/bin/php/php-8.5.12-Win32-vs17-x64"
```

**Jika Laragon tidak menulis ulang** (masih `php-8.3.30...`), edit manual keempat baris
tersebut agar menunjuk folder baru. Jangan mengubah baris `<IfModule mime_module>`.

Verifikasi cepat tanpa membuka editor:

```powershell
Select-String -Path 'D:\laragon\etc\apache2\mod_php.conf' -Pattern 'php-8\.3\.30'
```

**Ekspektasi:** tidak ada output.

---

### Langkah 7 — Restart & diagnosa

**Tujuan:** memuat modul baru; kalau gagal, tahu penyebabnya dalam < 2 menit.

```powershell
# tray Laragon: Stop All, lalu Start All
Get-Content 'D:\laragon\bin\apache\httpd-2.4.66-260223-Win64-VS18\logs\error.log' -Tail 30
```

**Ekspektasi:** Apache start, log berisi baris `resuming normal operations` tanpa error
`Cannot load ... into server`.

**Jika gagal** — tiga penyebab tersering, berurutan:

| Gejala di `error.log` | Penyebab | Perbaikan |
|---|---|---|
| `Cannot load .../php8apache2_4.dll into server: %1 is not a valid Win32 application` | Salah varian build (NTS) atau ZIP 32-bit | Unduh ulang varian **x64 Thread Safe** |
| `... The specified module could not be found` / `... VCRUNTIME140.dll` | VC++ Redistributable 2015–2022 x64 belum ada | Pasang redist, restart Windows bila diminta. Di mesin ini **sudah terpasang** (v14.51), jadi kalau muncul, curigai folder PHP tidak lengkap |
| `... PHPIniDir ... No such file` atau Apache memuat versi lama | Path di `mod_php.conf` / nama folder tidak persis sama | Betulkan langkah 6, pastikan nama folder = isi `mod_php.conf` |
| Port 80 sudah dipakai | Proses `httpd.exe` lama masih hidup | `Stop-Process -Name httpd -Force` lalu Start All |
| `PHP Deprecated: report_memleaks is deprecated` | Baris 472 belum dihapus | Kembali ke Langkah 5 |

---

### Langkah 8 — Verifikasi CLI

**Tujuan:** memastikan binary baru yang dipakai shell.

> **Penting:** buka **terminal Laragon baru**. Terminal yang sudah terbuka memegang `PATH`
> lama dan akan terus melaporkan 8.3.30.

```powershell
php -v
php -r "echo PHP_VERSION_ID, PHP_EOL;"
php -m
where.exe php
Get-Command php -All
```

**Ekspektasi:**

- `PHP 8.5.12 (cli) ...`
- `80512`
- `php -m` memuat 14 extension dari langkah 4
- Resolusi executable aktif menunjuk `D:\laragon\bin\php\php-8.5.12-Win32-vs17-x64\php.exe`.
  `where.exe php` dan `Get-Command php -All` boleh menampilkan versi lain yang sengaja
  dipasang, tetapi urutan resolusi harus benar. Jangan menghapus entri PATH milik aplikasi
  lain tanpa meninjau pemiliknya. Di PowerShell, jangan gunakan `where php` karena
  `where` biasanya alias `Where-Object`.

**Jika gagal:** `php` masih 8.3.30 → tutup semua terminal, cek `PATH`, ulangi.
Extension hilang → kembali ke langkah 4c.

---

### Langkah 9 — Verifikasi SAPI web

**Tujuan:** membuktikan **Apache** (bukan hanya CLI) memuat 8.5.12. Tanpa langkah ini,
kamu bisa merasa aman padahal web masih rentan.

```powershell
cd D:\laragon\www\website-kolam
if (Test-Path 'public\_check.php') { throw 'File cek sudah ada. Jangan timpa file operator.' }
$probe = @'
<?php
header('Content-Type: application/json');
echo json_encode([
    'version' => PHP_VERSION,
    'sapi' => PHP_SAPI,
    'ini' => php_ini_loaded_file(),
    'upload_progress' => (bool) ini_get('session.upload_progress.enabled'),
    'expose_php' => (bool) ini_get('expose_php'),
    'fatal_error_backtraces' => (bool) ini_get('fatal_error_backtraces'),
    'extensions' => get_loaded_extensions(),
]);
'@
Set-Content -Path 'public\_check.php' -Value $probe -Encoding ascii
```

Buka `http://website-kolam.test/_check.php`.

**Ekspektasi:** JSON memuat `version: "8.5.12"`, `sapi: "apache2handler"`, path `ini`
ke folder baru, serta `upload_progress`, `expose_php`, dan `fatal_error_backtraces` bernilai
`false`. Semua extension yang diperlukan aplikasi juga harus termuat pada **SAPI web**.

Gunakan URL virtual host yang benar-benar terkonfigurasi; `website-kolam.test` adalah
catatan host sebelumnya. Probe membocorkan versi/path lokal: gunakan hanya selama akses
jaringan dibatasi, jangan commit atau deploy, dan **hapus juga ketika verifikasi gagal**.

**WAJIB dihapus setelah verifikasi:**

```powershell
Remove-Item public\_check.php
```

**Jika gagal:** masih `8.3.30` → `mod_php.conf` belum menunjuk folder baru (langkah 6);
setelah memperbaiki, `Stop All` → `Start All`, lalu muat ulang dengan `Ctrl+F5`.
Jika `apache2handler` tidak muncul atau file justru terunduh → `AddType` hilang dari
`mod_php.conf`; kembalikan blok `<IfModule mime_module>` dari file `.bak`.

---

### Langkah 10 — Verifikasi dependency & aplikasi

**Tujuan:** memastikan platform check dan bootstrap Laravel sehat di 8.5.

```powershell
cd D:\laragon\www\website-kolam
composer check-platform-reqs
php artisan about
php artisan config:clear
php artisan route:list
php artisan migrate:status
```

**Ekspektasi:**

- `check-platform-reqs`: semua requirement `success`. **Catatan:** perintah ini **sudah
  GAGAL hari ini** di PHP 8.3.30 (karena `symfony/yaml v8.0.15` menuntut `php >=8.4`),
  jadi kalau Fase 0 (§5.4) sudah dikerjakan, kegagalan itu sudah hilang **sebelum** upgrade.
  Kalau masih gagal, baca requirement yang disebutkan; PHP, extension, dan platform API
  semuanya perlu diperiksa. Jangan menyimpulkan penyebabnya sebelum melihat output.
- `artisan about`: tabel environment tampil, `PHP Version 8.5.12`.
- `route:list`: 3 route web (`/`, `/sitemap.xml`, `/robots.txt`) + route Filament.
- `migrate:status`: tidak ada migrasi berstatus `Pending`.

**Jika gagal:**

- `Root composer.json requires php ^8.3 but your php version ...` → jangan panik, cek
  `php -v` di terminal yang sama (kemungkinan PATH lama).
- `Class "IntlDateFormatter" not found` / `Call to undefined function mb_strlen()` →
  extension belum termuat (langkah 4c).
- Error `vendor/composer/platform_check.php` → baca requirement yang gagal dan cek binary,
  extension, serta lock file. `composer install` tidak menyelesaikan ketidakcocokan nyata.
- Error yang menyebut `symfony/yaml` → pastikan checkpoint Fase 0 dan versi runtime aktif
  benar; jangan mengulang update dependency secara membabi buta pada fase runtime.

---

### Langkah 11 — Test suite + penangkapan deprecation

**Tujuan:** bukti regresi, sekaligus menangkap deprecation 8.5 yang tidak terlihat saat
test biasa.

```powershell
cd D:\laragon\www\website-kolam
php artisan test --compact
if ($LASTEXITCODE -ne 0) { throw 'Test gagal setelah upgrade runtime. Investigasi sebelum lanjut.' }
php artisan test --display-deprecations --display-phpunit-deprecations --display-warnings
if ($LASTEXITCODE -ne 0) { throw 'Run diagnostik test gagal. Periksa laporan.' }
# Periksa log yang benar-benar digunakan, termasuk log harian bila dikonfigurasi.
Get-ChildItem 'storage\logs\*.log' | Select-String -Pattern 'deprecated' | Select-Object -Last 30
Get-Content 'D:\laragon\tmp\php_errors.log' -Tail 30
```

**Ekspektasi:**

- `php artisan test` → **32 passed** (0 failed, 0 risky).
- Run kedua: tidak ada deprecation yang berasal dari `app/`, `database/`, `routes/`, `tests/`.
  Deprecation dari `vendor/` harus ditriase: warning nonfatal tanpa dampak dapat diterima
  dengan catatan dan tindak lanjut; error yang mengganggu auth, upload, response, atau test
  tetap blocker. Jangan mengabaikan semuanya hanya karena berasal dari vendor.
- Periksa log Laravel, PHP, dan Apache sejak waktu upgrade. Tidak adanya teks `Deprecated:`
  di satu log bukan bukti tidak ada deprecation; perhatikan konfigurasi `error_reporting`,
  kanal log, dan bahwa PHPUnit hanya mencakup jalur yang dieksekusi.

Kalau ada file PHP yang tersentuh (mis. karena perbaikan deprecation):

```powershell
vendor/bin/pint --dirty --format agent
```

**Jika gagal:** satu test merah → baca pesan, tentukan apakah karena PHP 8.5 atau karena
perubahan lain. Kalau ragu, rollback (langkah 13) lalu telusuri di 8.3.30 sebagai baseline.

---

### Langkah 12 — Verifikasi manual (5 menit)

**Tujuan:** memeriksa perilaku yang tidak tercakup test otomatis, khususnya upload,
validasi MIME, dan pemrosesan gambar. Keberhasilan upload tidak membuktikan setiap
extension terpakai; verifikasi daftar extension CLI/web tetap dilakukan terpisah.

Landing page `http://website-kolam.test/`:

- [ ] Hero tampil dengan foto (bukan fallback), headline & subjudul terisi.
- [ ] Bilah kepercayaan: 4 angka tampil.
- [ ] Kartu Layanan: gambar/ikon tampil.
- [ ] Portofolio: thumbnail proyek tampil.
- [ ] Galeri Video: poster tampil, klik → modal play jalan, `Esc` menutup.
- [ ] FAQ: akordeon buka/tutup, hanya satu terbuka.
- [ ] Form konsultasi: submit menyusun pesan dan membuka `wa.me`.
- [ ] Tombol WhatsApp melayang ada di semua posisi scroll.
- [ ] Tidak ada error di Console browser.

Panel admin `http://website-kolam.test/admin`:

- [ ] Login `admin@kolamprima.test` berhasil.
- [ ] Tabel Proyek menampilkan thumbnail (bukan kolom kosong).
- [ ] **Upload gambar baru pada Proyek uji** → tersimpan & tampil. Ini memverifikasi alur
      upload, bukan membuktikan extension `zip` dipakai; cek extension secara terpisah.
- [ ] Halaman **Pengaturan Situs**: upload logo/hero berhasil, nilai stat tersimpan.
- [ ] Resource FAQ/Paket/Layanan/Galeri Video terbuka tanpa error.

Gunakan record/file uji dan kembalikan perubahan manual setelah pemeriksaan. Upload dan
perubahan Pengaturan Situs **menulis data**, walaupun upgrade runtime sendiri tidak
menjalankan migrasi skema.

**Jika gagal:** upload gagal dengan pesan MIME/ukuran → cek `extension=fileinfo`, `gd`,
`zip`, dan `upload_max_filesize`/`post_max_size` di `php.ini` baru.

---

### Langkah 13 — Rollback

**Langkah kondisional**, bukan langkah yang harus dijalankan setelah smoke test sukses.
Gunakan hanya jika validasi gagal atau ketika menguji rollback secara sengaja. Jika menguji,
aktifkan kembali versi patched dan ulangi verifikasi sebelum menyatakan pekerjaan selesai.

**Tujuan:** kembali ke checkpoint pra-runtime, termasuk metadata dependency bila sudah diubah.

```powershell
# 13a. Batasi akses jaringan; Stop All terlebih dahulu.
# UI: tray Laragon > PHP > Version > 8.3.30
# 13b. Kembalikan wiring bila perlu
Copy-Item 'D:\laragon\etc\apache2\mod_php.conf.bak-8.3.30' 'D:\laragon\etc\apache2\mod_php.conf' -Force
# 13c. Jika Langkah 14/perubahan metadata telah dijalankan, pulihkan checkpoint Fase 1.
# Jangan menimpa perubahan operator yang dibuat setelah checkpoint tanpa persetujuan.
Set-Location 'D:\laragon\www\website-kolam'
Copy-Item 'D:\laragon\backup\website-kolam-pre-runtime-upgrade\composer.json' '.\composer.json' -Force
Copy-Item 'D:\laragon\backup\website-kolam-pre-runtime-upgrade\composer.lock' '.\composer.lock' -Force
# 13d. Start All, buka terminal baru, lalu verifikasi:
php -v
composer install
if ($LASTEXITCODE -ne 0) { throw 'Pemulihan vendor gagal. Baca error sebelum melanjutkan.' }
composer check-platform-reqs
php artisan test --compact
# Ulangi probe SAPI web pada Langkah 9 untuk versi 8.3.30, lalu hapus probe.
```

**Ekspektasi:** CLI **dan web** kembali ke `PHP 8.3.30`, dependency berada pada checkpoint
pasca-Fase 0, dan baseline test kembali lulus. Runtime ini rentan: rollback hanya langkah
diagnostik sementara dengan akses jaringan dibatasi, **bukan kondisi akhir yang aman**.

Catatan: simpan folder PHP baru sampai investigasi selesai. Rollback runtime tidak
mengembalikan perubahan upload/pengaturan saat smoke test. Bersihkan data uji secara
terarah; restore database/upload hanya bila diperlukan dan disetujui, karena restore
menyeluruh dapat menimpa pekerjaan setelah backup. **Rollback Fase 0** memakai checkpoint
pra-update yang berbeda (§8.1), bukan checkout HEAD.

---

### Langkah 14 — Kunci hasilnya di repo

**Tujuan:** supaya sesi berikutnya (manusia atau agen) tidak bingung versi mana yang berlaku.

Perubahan yang diusulkan — **butuh persetujuan operator** karena menyentuh metadata
dependency dan dokumen:

1. **Default: pertahankan requirement PHP aplikasi** jika upgrade hanya menyentuh runtime
   lokal. Tidak wajib menaikkan minimum aplikasi agar runtime Laragon bisa memakai 8.5.
   Jika operator secara terpisah menyetujui minimum aplikasi 8.5 patched, pertimbangkan
   `"php": ">=8.5.12 <8.6"` (sesuaikan batas patch dengan hasil verifikasi terbaru), bukan
   `^8.5` yang masih menerima 8.5.0. Ini membatasi kompatibilitas aplikasi ke cabang 8.5;
   pastikan target deployment dan proyek tim siap. Setelah perubahan, jalankan
   `composer update --lock`, tinjau diff manifest/lock, lalu ulangi platform check, audit,
   dan test. Perintah Composer tetap dapat menjalankan scripts; jangan menganggapnya
   sekadar edit hash tanpa efek samping. Requirement Composer bukan pengganti patch host.
2. `TODO.md` → tambahkan entri:
   `- [x] Runtime PHP 8.5.12 (security s/d 31 Des 2029). Cek patch: <tanggal>.`
3. `DOKUMENTASI.md` baris 27 → `| Bahasa | PHP | 8.3.30 |` menjadi `| Bahasa | PHP | 8.5.12 |`.
4. `AGENTS.md` baris 8 **dan** `CLAUDE.md` baris 8 (keduanya identik, 7.136 byte) →
   `running on PHP 8.4` menjadi `running on PHP 8.5.12`. Saat ini keduanya **salah**
   (aktual 8.3.30), jadi perbaiki ke versi yang benar-benar terpasang.
5. Commit: `chore(php): upgrade runtime 8.3.30 -> 8.5.12 (php2shell CVE-2026-109618/109619)`.

**Jika ditolak:** catat di [Lampiran C](#lampiran-c--log-eksekusi) bahwa langkah ini
sengaja dilewati, beserta alasannya.

> **Catatan rollback:** menaikkan minimum PHP aplikasi mengharuskan pemulihan metadata
> Composer checkpoint Fase 1 sebelum kembali ke 8.3. Upgrade runtime tidak otomatis
> mengharuskan perubahan minimum aplikasi. Keempat proyek lain memiliki manifest sendiri,
> tetapi tetap terpengaruh oleh switch runtime Laragon.

---

### Langkah 15 — Cegah kambuh

**Tujuan:** menutup akar masalah (proses patch), bukan hanya gejalanya.

- [ ] Tambahkan `composer audit` ke script test (`composer.json` → `scripts.test`), atau ke
      GitHub Actions di repo `https://github.com/ItsMeAldy/kolam-prima`.
- [ ] Aktifkan **Dependabot** (atau Renovate) untuk `composer.lock` dan `.github/workflows`.
- [ ] Jadwal bulanan (catat di kalender): cek `https://www.php.net/releases/` + jalankan
      `composer audit`. Ritme rilis PHP ±4 minggu; security release bisa mendadak seperti
      21 Okt 2026.
- [ ] Saat Fase 10 (deploy) dikerjakan: pin runtime ke `8.5.x` **terbaru**, dan setelah
      setiap upgrade PHP **restart queue worker / long-running process** — proses tersebut
      tetap memakai binary yang ia mulai.
- [ ] WAF bukan pengganti patch (CISA eksplisit menyebutnya sebagai perlindungan sementara).

---

## 7. Definition of Done

Upgrade dianggap selesai **hanya** jika semua baris ini tercentang dan buktinya ditempel
ke [Lampiran C](#lampiran-c--log-eksekusi).

| # | Kriteria | Cara cek | Target | Bukti |
|---|---|---|---|---|
| 0a | Audit dependency bersih | `composer audit` | **0 advisory** (dari 13) | |
| 0b | Platform check bersih | `composer check-platform-reqs` | semua `success` | |
| 0c | Test setelah Fase 0 | `php artisan test --compact` | **32 passed** | |
| 1 | PHP CLI versi baru | `php -v` (terminal baru) | versi patched target; 8.5.12 atau patch lebih baru yang diverifikasi | |
| 2 | Resolusi executable benar | `where.exe php`, `Get-Command php -All` | executable aktif dari folder target | |
| 3 | Extension lengkap | `php -m` | 14 extension §3 | |
| 4 | SAPI web versi baru | probe JSON Langkah 9 lalu dihapus | versi target, apache2handler, ini baru, hardening false, extension lengkap | |
| 5 | File cek dihapus | `Test-Path public\_check.php` | `False` | |
| 6 | Wiring Apache | `Select-String mod_php.conf 'php-8.3.30'` | tidak ada hasil | |
| 7 | Platform check (post-upgrade) | `composer check-platform-reqs` | semua success | |
| 8 | Bootstrap app | `php artisan about` | tanpa error, PHP 8.5.12 | |
| 9 | Test suite | `php artisan test --compact` | **32 passed** | |
| 10 | Deprecation app | log + `--display-deprecations` | tidak ada dari `app/`, `database/`, `routes/`, `tests/` | |
| 10b | Hardening aktif | `php -i` grep 3 directive | `Off` / `Off` / `Off` | |
| 10c | Composer modern | `composer --version` | ≥ 2.8.11 | |
| 11 | Upload gambar | `/admin` → Proyek → upload | tersimpan & tampil | |
| 12 | Panel admin | login + 6 resource terbuka | tanpa error | |
| 13 | Landing page | manual (Langkah 12) | semua checklist lulus | |
| 14 | Versi dan keputusan metadata tercatat | dokumen yang disetujui operator / log eksekusi | versi aktual dan apakah minimum PHP diubah; bila Langkah 14 ditolak, alasan dicatat | |
| 15 | Checkpoint tersedia / rollback diuji bila dipilih | metadata per fase, konfigurasi, DB/upload | jika rollback diuji, kembali lagi ke runtime patched dan ulangi validasi | |
| 16 | Empat proyek lain tervalidasi | platform check, test tersedia, smoke test per proyek | hasil dan keterbatasan dicatat; tidak ada regresi blocker | |
| 17 | Akses jaringan ditinjau | binding, firewall, tunnel, port forwarding | exposure aktual dicatat, bukan diasumsikan nol | |

**Tidak boleh** menyatakan selesai bila ada gate wajib gagal, khususnya #0a, #4, #9,
dan #16 untuk runtime bersama. Catat pengecualian yang disetujui secara eksplisit;
pengecualian bukan bukti aman. Versi akhir harus patched, bukan versi rollback rentan.

---

## 8. Rollback & Matriks Kegagalan

### 8.1 Prosedur ringkas

**Fase 1 (runtime):** ikuti Langkah 13, termasuk pemulihan metadata dari checkpoint
`website-kolam-pre-runtime-upgrade` bila berubah. Verifikasi CLI **dan SAPI web**, platform,
dan baseline test, bukan hanya `php -v`. Batasi akses jaringan selama kembali ke runtime
rentan. Rollback database/upload hanya untuk perubahan data yang memang hendak dibatalkan,
dengan persetujuan operator. Bila rollback diuji setelah upgrade sukses, aktifkan kembali
versi patched dan ulangi semua gate sebelum menutup pekerjaan.

**Fase 0 (dependency), terpisah:** pulihkan salinan pra-update, bukan HEAD. Perintah berikut
mengasumsikan lokasi checkpoint default; jika operator memakai lokasi lain, sesuaikan
literal path berdasarkan catatan eksekusi.

```powershell
Set-Location 'D:\laragon\www\website-kolam'
# Tinjau diff dan amankan perubahan yang dibuat setelah checkpoint sebelum menimpa.
git --no-pager diff -- composer.json composer.lock
Copy-Item 'D:\laragon\backup\website-kolam-pre-security-update\composer.json' '.\composer.json' -Force
Copy-Item 'D:\laragon\backup\website-kolam-pre-security-update\composer.lock' '.\composer.lock' -Force
composer install
if ($LASTEXITCODE -ne 0) { throw 'Pemulihan dependency gagal. Jangan mengabaikan platform.' }
composer check-platform-reqs
composer audit
php artisan test --compact
```

**Batas rollback:** checkpoint awal memang memiliki platform mismatch YAML pada PHP 8.3.
`composer install` dapat menolaknya, sehingga backup dua file tidak menjamin vendor dapat
dibangun ulang di runtime lama. Jika ini terjadi, jangan memakai `--ignore-platform-reqs`
untuk menamai rollback berhasil. Batasi akses jaringan dan tinjau opsi runtime patched ≥8.4
atau dependency patched yang menerima 8.3 bersama operator. Kondisi akhir rollback Fase 0
mengembalikan advisory awal (jumlah aktual bisa berubah), bukan kondisi aman.

Jika diperlukan rollback lingkungan yang identik tanpa resolver/install ulang, sepakati
backup lengkap `vendor` sebelum Fase 0 dan ruang disk yang dibutuhkan. Pemulihan vendor
tersebut hanya untuk investigasi baseline, tidak memperbaiki platform mismatch.

### 8.2 Matriks gejala → perbaikan

| Gejala | Kemungkinan penyebab | Perbaikan |
|---|---|---|
| Apache tidak start, `is not a valid Win32 application` | Build NTS atau 32-bit | Unduh ulang x64 **Thread Safe** |
| Apache tidak start, `VCRUNTIME140.dll` hilang | VC++ Redist 2015–2022 x64 belum ada | Pasang redist, restart. Di mesin ini sudah ada (v14.51) — curigai folder PHP tidak lengkap |
| Apache start tapi `/_check.php` masih 8.3.30 | `mod_php.conf` tidak diperbarui | Langkah 6, lalu restart |
| `/_check.php` menampilkan source code | `AddType application/x-httpd-php .php` hilang | Kembalikan blok `<IfModule mime_module>` dari `.bak` |
| `PHP Startup: Unable to load dynamic library 'php_xxx.dll'` | `extension_dir` salah / `.dll` tidak ada | Langkah 4, cek `ext\` |
| `PHP Deprecated: report_memleaks is deprecated` | Baris 472 belum dihapus | Langkah 5 |
| `Class "IntlDateFormatter" not found` | `extension=intl` tidak aktif | Langkah 4c |
| `Call to undefined function mb_*()` | `extension=mbstring` tidak aktif | Langkah 4c |
| Upload gambar gagal / thumbnail rusak | `gd`/`fileinfo`/`zip` tidak termuat, atau `upload_max_filesize` berubah | Langkah 4c + cek batas upload |
| `composer check-platform-reqs` gagal `php ^8.3` | Terminal masih PATH lama | Tutup semua terminal, buka terminal Laragon baru |
| Platform check gagal `symfony/yaml requires php >=8.4` | PHP yang aktif masih <8.4 dan lock/vendor memakai YAML 8 | Periksa executable, lock, vendor, dan checkpoint; jika fase awal, ikuti gate §5.4, jangan update membabi buta |
| `composer` memuntahkan banyak `Deprecated:` | Composer masih 2.6.6 | Langkah 3b |
| `vendor/composer/platform_check.php` error | PHP/extension tidak sesuai atau metadata tidak sinkron | Baca requirement yang gagal, benahi runtime; install ulang hanya jika platform cocok |
| Test merah di satu file | Perbedaan perilaku 8.5 | Bandingkan dengan baseline 8.3.30 (rollback), isolasi penyebab |
| Deprecation dari `vendor/` | Kompatibilitas versi paket perlu ditinjau | Triase dampak; hanya terima warning nonfatal yang disepakati, blokir regresi fungsi |
| Panel `/admin` blank / tanpa CSS | Bisa error backend, asset hilang, atau URL/config salah | Cek status response, console/network, dan log; publish asset hanya bila bukti menunjukkan itu penyebabnya |
| Halaman putih, log kosong | `display_errors`/`error_log` menunjuk path salah | Cek `D:/laragon/tmp/php_errors.log` dan `storage/logs/laravel.log` |

---

## 9. Hardening Lanjutan (Out of Scope)

Ditemukan saat audit, **tidak** ditutup oleh upgrade PHP. Butuh plan terpisah (usul:
`PLAN-SECURITY.md`) sebelum Fase 10 (deployment).

| # | Temuan | Risiko | Usul |
|---|---|---|---|
| 1 | `APP_DEBUG=true` di `.env` **dan** `.env.example` | Stack trace + path + query bocor bila lupa diubah di produksi | Wajib `false` di produksi; tambah cek di checklist deploy |
| 2 | `.env` berisi `APP_KEY` dan secret di root proyek | Kebocoran key mengancam integritas/kerahasiaan data terenkripsi dan cookie | Jangan commit atau masukkan secret ke artifact publik; inject secret secara aman saat deploy. Root proyek tidak boleh menjadi document root; arahkan ke `public/`. Klaim bersih dari history perlu audit tersendiri |
| 3 | Akun default `admin@kolamprima.test` / `password` (`AdminUserSeeder.php:15-20`) | Takeover panel admin | Ganti sebelum deploy; hapus seeder kredensial default |
| 4 | `User::canAccessPanel()` mengembalikan `true` untuk **semua** user (`app/Models/User.php:25-28`) | User mana pun bisa masuk panel | Batasi ke role/permission sebelum menambah user |
| 5 | Upload `->image()` menerima **`image/svg+xml`** (Filament memetakan ke `mimetypes:image/*`), disajikan langsung dari `public/storage` | **Stored XSS** via SVG yang di-upload admin | Whitelist MIME eksplisit (`jpeg,png,webp`), atau sanitasi SVG |
| 6 | Catatan audit: raw `json_encode` pada layout/home dan konten admin di atribut Alpine | Potensi breakout script/atribut; perlu validasi sink dan payload per konteks | Gunakan encoding sesuai konteks: JS expression berbeda dengan JSON-LD `application/ld+json`; jangan mengganti JSON-LD dengan `Js::from()` karena hasilnya dapat berupa ekspresi JS, bukan JSON |
| 7 | `/admin` **sudah** punya rate-limit (`rateLimit(5)`, `vendor/filament/filament/src/Auth/Pages/Login.php:70`), tapi **belum ada 2FA** | Brute force terbatas; tidak ada faktor kedua | Aktifkan `->multiFactorAuthentication()` di `AdminPanelProvider` |
| 8 | Aset Filament ter-commit (`public/css\|js/fonts/filament`) | Lupa re-publish saat upgrade Filament | Catat di checklist upgrade Filament (lihat `TODO.md` E3d) |
| 9 | MySQL `root` tanpa password (catatan lokal) | Proses lokal atau web worker yang disusupi dapat mengakses seluruh DB; bukan otomatis aman karena lokal | Gunakan akun aplikasi berhak minimum dan password; batasi bind/firewall, jangan ekspos MySQL ke jaringan tidak dipercaya |
| 10 | `expose_php=On` (diperbaiki di langkah 5) | Bocor versi | Sudah ditutup runbook ini |
| 11 | `upload_max_filesize=2G` / `post_max_size=2G` di `php.ini` | Disk penuh / DoS upload; jauh di atas kebutuhan (form Filament maks 50 MB) | Turunkan ke 64M saat Fase 10 |
| 12 | `display_errors=On` (baris 441) | Stack trace tampil di browser bila ada error | `Off` di produksi (di lokal boleh tetap On) |

> **Catatan revisi:** baris #5 sebelumnya berbunyi "`/admin` tanpa rate-limit / 2FA" —
> itu keliru untuk bagian rate-limit. Filament 5 sudah memuat `rateLimit(5)`. Yang benar-benar
> belum ada hanya 2FA. Baris #4, #5, #6, #11, #12 adalah temuan baru dari audit 13 Nov 2026.

---

## Lampiran A — Evaluasi Migrasi Bahasa (Kenapa Ditolak)

### A.1 Pertanyaan awal

Operator meminta rencana **migrasi dari Laravel ke bahasa lain**, dengan pemicu: kejadian
CVE PHP (php2shell). Permintaan itu diuji sebelum dieksekusi.

### A.2 Ukuran pekerjaan sebenarnya

| Komponen | Jumlah | Beban pindah stack |
|---|---|---|
| Controller | 1 (`HomeController`) | Rendah |
| Model | 8 | Rendah |
| Filament Resource | 6 + 1 halaman setting | **Tinggi** (tabel, form, filter, upload, auth) |
| Blade section | 13 + layout + 3 komponen | Sedang (Tailwind/Alpine bisa dipakai ulang) |
| Test | 32 (5 file) | Sedang (harus ditulis ulang) |
| Migrasi DB | 13 | Rendah |
| Seeder | 8 | Rendah |

**Kesimpulan:** ~70% beban migrasi ada di **mengganti panel admin Filament**, bukan di
landing page satu halaman.

### A.3 Lima alasan migrasi bukan tuas yang tepat untuk CVE ini

1. **Ini bug runtime, bukan bug aplikasi.** Upgrade 8.3.30 → 8.5.12 menutupnya; migrasi
   stack 3–15 hari tidak menutup apa pun yang belum ditutup patch.
2. **Migrasi memindahkan kewajiban patch, tidak menghapusnya.** Go, Node, Python, JVM
   semuanya punya riwayat CVE RCE di runtime/stdlib. Yang berubah hanya jadwal dan nama
   paketnya.
3. **Belum deploy menurunkan exposure, bukan membuktikan nol.** Periksa akses LAN,
   firewall, binding Apache, tunnel, dan port forwarding. Urgensinya adalah memperbarui
   runtime lokal dan mencegah versi rentan terpapar, bukan menulis ulang aplikasi.
4. **Ada risiko aplikasi yang terpisah dari PHP core**, termasuk akun admin default,
   debug produksi, dan batas upload (§9). Tanpa asesmen menyeluruh, jangan memberi ranking
   seolah core PHP bukan risiko besar. Migrasi tidak otomatis memperbaiki risiko tersebut.
5. **Ada solusi arsitektural yang lebih tepat** bila motifnya "tidak mau jadi penanggung
   jawab patch runtime": landing page ini 100% read-only (tanpa simpan lead, tanpa auth
   publik) → bisa disajikan sebagai static/SSG di platform managed (runtime bukan urusanmu),
   sementara admin tetap Laravel di balik akses terbatas.

### A.4 Bila motif berubah (belajar / portofolio / tuntutan kerja)

Catatan biaya paritas, disimpan agar tidak perlu riset ulang:

| Stack tujuan | Kelebihan | Biaya paritas (landing + admin + upload + test) |
|---|---|---|
| Python + Django | Admin bawaan paling mirip Filament | 3–5 hari |
| TypeScript + Next.js + Payload CMS 3 | Modern, admin React, Postgres | 5–8 hari |
| TypeScript + AdonisJS | Paling mirip Laravel di Node | 5–7 hari (admin dirakit sendiri) |
| Go + Echo/Fiber + HTMX | Runtime paling ringan | 10–15 hari (admin dibangun manual) |

### A.5 Penutup

Keputusan: **patch runtime, jangan rewrite**. Dokumen ini karena itu adalah runbook upgrade
PHP 8.5.12 + penutupan CVE dependency. Bila suatu hari motifnya benar-benar berubah menjadi
belajar/portofolio, dokumen ini menjadi **lampiran latar belakang**, bukan rujukan utama migrasi.

### A.6 Fakta yang memperkuat keputusan (audit 13 Nov 2026)

Bukti tambahan yang membuat migrasi makin tidak masuk akal untuk kasus ini:

- Pemeriksaan pola sebelumnya tidak menemukan sink RCE tertentu di kode aplikasi,
  termasuk `eval`, `exec`, dan include dinamis. Ini **bukan audit keamanan lengkap** dan
  tidak mencakup semua alur framework/vendor atau membuktikan tidak ada celah aplikasi.
- Route publik yang ditulis aplikasi terdiri dari tiga GET read-only (`/`, `/sitemap.xml`,
  `/robots.txt`); konsultasi memakai Alpine untuk membuka `wa.me`. Namun server tetap
  menerima input HTTP dan endpoint login/Livewire/framework harus dihitung terpisah.
- Mutasi aplikasi yang direncanakan lewat panel Filament terautentikasi + CSRF. Kontrol ini
  tidak menggantikan pemeriksaan authorization dan kondisi tiap advisory dependency.
- Pemeriksaan penggunaan fitur tertentu belum menemukan jalur untuk sebagian advisory;
  reachability seluruh advisory belum dibuktikan. Audit ulang dan patch tetap diperlukan.

Kesimpulan: belum ada kebutuhan rewrite yang ditunjukkan oleh masalah PHP2SHELL.
Memperbarui runtime menutup celah runtime yang diketahui tanpa biaya penulisan ulang;
risiko aplikasi/dependency tetap harus ditangani pada lapisannya sendiri.

---

## Lampiran B — Referensi & Sumber

Diakses 12–13 Nov 2026. Ketentuan patch/CVE dapat berubah — verifikasi ulang sebelum eksekusi.

| # | Sumber | URL | Dipakai untuk |
|---|---|---|---|
| 1 | CISA — Critical Vulnerabilities in PHP Require Immediate Patching (21 Okt 2026, diperbarui 26 Okt 2026) | `https://www.cisa.gov/news-events/alerts/2026/10/21/critical-vulnerabilities-php-require-immediate-patching` | Versi terpatch, KEV, SAPI terdampak, mitigasi resmi |
| 2 | JFrog Research — PHP2Shell: All You Need to Know | `https://research.jfrog.com/post/php2shell-all-you-need-to-know-about-cve-2026-109618-and-cve-2026-109619/` | Mekanisme, precondition, mitigasi parsial (`session.upload_progress`, `pm.max_requests`, `fatal_error_backtraces`) |
| 3 | PHP — Release announcement 8.5.12 (21 Okt 2026) | `https://www.php.net/releases/8_5_12.php` | Konfirmasi security release |
| 4 | PHP for Windows — Downloads + `sha256sum.txt` | `https://downloads.php.net/~windows/releases/` | Nama berkas & **checksum resmi** build VS17 x64 TS |
| 5 | PHP — Supported Versions | `https://www.php.net/supported-versions.php` | Tanggal dukungan 8.3/8.4/8.5 |
| 6 | PHP — Migrating 8.4 → 8.5 (BC breaks) | `https://www.php.net/manual/en/migration85.php` | Daftar deprecation/inkompatibilitas |
| 7 | PHP — Runtime Configuration (errorfunc) | `https://www.php.net/manual/en/errorfunc.configuration.php` | `fatal_error_backtraces` (baru di 8.5), `report_memleaks` (deprecated di 8.5) |
| 8 | PHP — News Archive 2026 (informational advisory 23 Okt 2026) | `https://www.php.net/archive/2026.php` | `CVE-2026-115560` + mitigasi `session.upload_progress.enabled=0` |
| 9 | PHP — ChangeLog 8 | `https://www.php.net/ChangeLog-8.php` | 8.5.12 = stable terbaru per 13 Nov 2026; 8.5.13 masih RC |
| 10 | Laravel — Release Notes / Support Policy | `https://laravel.com/framework/docs/releases` | Laravel 12 → PHP 8.2–8.5, Laravel 13 → PHP 8.3–8.5 |
| 11 | Symfony — Releases | `https://symfony.com/releases` | 7.4 = LTS (bug fix s/d Nov 2028, security s/d Nov 2029); 8.0 unmaintained |
| 12 | Composer — CHANGELOG | `https://github.com/composer/composer/blob/main/CHANGELOG.md` | 2.8.11+ memuat fix deprecation PHP 8.5 |
| 13 | Laragon — Multi-Version Services | `https://laragon.org/docs/multi-version` | Prosedur switch versi |
| 14 | Laragon Wiki — Change PHP version | `https://github.com/leokhoa/laragon/wiki/⬆️Change-PHP-version` | Struktur folder `bin/php` |
| 15 | NCSC-NL — php2shell (operational info) | `https://github.com/NCSC-NL/php2shell` | Daftar software terdampak |
| 16 | Wiz Research — PHP2Shell | `https://www.wiz.io/blog/php2shell-php-rce` | Root cause, demonstrasi pre-auth RCE (termasuk Laravel 13.11) |
| 17 | PHP — GHSA-w8hw-w9fx-j79w | `https://github.com/php/php-src/security/advisories/GHSA-w8hw-w9fx-j79w` | Sumber primer CVE-2026-109618; OOM dan batas mitigasi |
| 18 | PHP — GHSA-6wmf-4rv5-p3rp | `https://github.com/php/php-src/security/advisories/GHSA-6wmf-4rv5-p3rp` | Sumber primer CVE-2026-109619; producer/consumer, worker, dan jalur non-session |

---

## Lampiran C — Log Eksekusi

Diisi saat runbook dijalankan. Satu baris per langkah, jangan dibersihkan.

| Tanggal | Langkah | Operator | Hasil | Catatan / bukti |
|---|---|---|---|---|
| | **F0-a** — Checkpoint pra-update + baseline | | | Path checkpoint; versi CLI/Composer; audit aktual; test aktual |
| | **F0-b** — Dry-run update + persetujuan cakupan | | | Daftar perubahan aktual; bandingkan catatan lama 21 update |
| | **F0-c** — Update dependency | | | |
| | **F0-d** — Verifikasi Fase 0 | | | Audit 0 / platform success / semua test baseline lulus / review asset |
| | 1 — Checkpoint pra-runtime + backup | | | Metadata pasca-Fase 0, konfigurasi, SQL, upload; hasil verifikasi backup |
| | 2 — Unduh + checksum | | | SHA256 lokal: |
| | 3 — Ekstrak | | | Folder: |
| | 3b — Update Composer | | | `composer --version`: |
| | 4 — `php.ini` baru | | | Sisa path lama: |
| | 5 — Hardening ini | | | |
| | 6 — Switch + audit `mod_php.conf` | | | |
| | 7 — Restart | | | |
| | 8 — CLI | | | `php -v` output: |
| | 9 — SAPI web | | | `/_check.php` output: |
| | 10 — Dependency & aplikasi | | | |
| | 11 — Test | | | `32 passed` / assertions: |
| | 12 — Manual | | | |
| | 13 — Rollback (jika dipakai) | | | Fase/checkpoint dipulihkan; hasil CLI/web/test; versi patched akhir |
| | 14 — Repo | | | Commit: |
| | 15 — Pencegahan | | | |
| | Proyek bersama — klinik-kasir | | | Platform/test/smoke test; keterbatasan |
| | Proyek bersama — sistem-pembayaran-klinik | | | Platform/test/smoke test; keterbatasan |
| | Proyek bersama — web-igrgto | | | Platform/test/smoke test; keterbatasan |
| | Proyek bersama — yayasan-bhakti-abu-rohana | | | Platform/test/smoke test; keterbatasan |
| | Exposure jaringan | | | Binding/firewall/tunnel/port forwarding aktual |

**Catatan insiden / penyimpangan dari runbook:**

```text
(tulis di sini: apa yang berbeda dari rencana, error yang muncul, cara diperbaiki)
```

**Verifikasi akhir yang menandatangani dokumen ini:**

```text
composer audit                          -> 
composer check-platform-reqs            -> 
php -v                                  -> 
where.exe php / Get-Command php -All    -> 
php -i + Select-String (3 directive)    -> 
http://website-kolam.test/_check.php     -> (sudah dihapus? ya/tidak)
php artisan test --compact              -> 
composer --version                      -> 
```

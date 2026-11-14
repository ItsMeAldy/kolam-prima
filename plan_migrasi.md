
# PLAN MIGRASI — Patch Runtime PHP 8.3.30 → 8.5.12 (php2shell)

> **Status:** BATCH A–G **SELESAI** · Fase runtime (switch PHP 8.5.12) **TUNTAS & TERVALIDASI** · Batch H: H1/H4 **selesai**, H2 **ditunda**, H3 **TBD**
> **Terakhir diperbarui:** 2026-11-14 (revisi 9: Batch H dijalankan — `composer audit` masuk `scripts.test` + workflow CI, catatan deployment ditulis; H2/H3 dibiarkan terbuka atas keputusan operator)
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

- [To-do Eksekusi & Pelacakan Progres](#to-do-eksekusi--pelacakan-progres)

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

## To-do Eksekusi & Pelacakan Progres

Checklist ini adalah pusat pelacakan; detail perintah tetap mengikuti §5–§8.
**Progres 13 Nov 2026: Batch A selesai — 5 tugas tuntas, A3 dipindahkan ke D0 atas persetujuan operator.**
**Batch B selesai: B1–B4 tuntas — Fase 0 hijau (audit 0 advisory, platform success, 32 test).**
**Batch C selesai: C1–C5 tuntas — checkpoint pra-runtime dibuat, SQL + upload terverifikasi, ZIP 8.5.12 TS cocok hash, terpasang di folder baru, Composer 2.11.2 siap.**
**Batch D & E selesai 14 Nov 2026 — PHP 8.5.12 aktif di CLI + SAPI web, D0 tertutup, login panel admin lulus.**
**Batch F selesai (dengan blocker non-runtime dicatat) — keempat proyek lain diuji di 8.5.12; F4 diperbaiki, WIP `klinik-kasir` & `public/build` `sistem-pembayaran-klinik` masih terbuka.**
**Batch G selesai 14 Nov 2026 — minimum PHP `>=8.5.12 <8.6`, dokumen diperbarui (tanpa commit), DoD terisi; tersisa Batch H (pencegahan kambuh).**
**Batch H dijalankan 14 Nov 2026 — H1 (`composer audit` di `scripts.test` + workflow CI) dan H4 (catatan deployment) selesai; H2 ditunda dan H3 belum ditetapkan atas keputusan operator, jadi Batch H selesai sebagian, bukan tuntas.**
Dependency sudah berubah (`composer.lock` + 21 paket di `vendor/`); runtime **aktif** masih PHP 8.3.30,
build 8.5.12 hanya ada sebagai folder berdampingan, dan Apache/MySQL tetap mati. Kerjakan satu batch,
catat hasilnya, lalu evaluasi gate sebelum batch berikutnya.

**Aturan tracking:**
- Centang `[x]` hanya setelah tugas benar-benar selesai dan bukti dicatat di Lampiran C.
- Status batch: `Belum mulai`, `Berjalan`, `Terblokir`, `Selesai`, atau `Ditunda`.
- Jika terblokir, catat perintah, error, checkpoint terakhir yang valid, dan tindakan berikutnya.
  Jangan mencentang tugas gagal atau melompati gate.
- Tugas kondisional/opsional yang tidak dilakukan tetap `[ ]`; catat `Tidak diperlukan`
  atau `Ditunda` beserta alasan. Itu bukan bukti validasi berhasil.
- Jangan commit, stash, reset, mengubah minimum PHP aplikasi, atau menimpa pekerjaan operator
  tanpa persetujuan. Commit/push di bawah adalah checkpoint persetujuan, bukan aksi otomatis.

| Batch | Fokus | Status | Bukti / blocker |
|---|---|---|---|
| A | Preflight & baseline | Selesai | A1/A2/A4/A5/A6 selesai. A3 dipindahkan ke **D0**; Apache **dan MySQL** tetap mati. Bukti di Lampiran C. |
| B | Fase 0 — dependency | **Selesai** | B1: dry-run 13 Nov 23:36 → `0/21/0`, yaml 8.0.15→7.4.23 (`b1-dryrun.txt`, `audit-b1.json`). B2: `composer update` exit 0, `0 installs, 21 updates, 0 removals`, 21 paket persis cakupan disetujui, `laravel/framework` tetap v13.35.0 (`b2-dryrun-confirm.txt`, `b2-update.txt`). B3: audit exit 0 **0 advisory**, platform exit 0 semua `success`, `php artisan test --compact` exit 0 **32 passed / 130 assertions** (`audit-b3.txt`, `audit-b3.json`, `platform-b3.txt`, `tests-b3.txt`). B4: diff lock = 21 versi saja; 43 file `public/` dan `bootstrap/cache` hash tidak berubah. |
| C | Backup pra-runtime & persiapan build | **Selesai** | C1: `D:\laragon\backup\website-kolam-pre-runtime-upgrade` (checkpoint baru, tidak menimpa yang lama) + `mod_php.conf.bak-8.3.30` dan `php.ini.bak-8.3.30` identik hash. C2: `website_kolam.sql` 151.958 byte / 16 tabel, exit 0, utf8mb4 valid, **uji restore ke `website_kolam_restore_check` lolos** (jumlah baris 16/16 tabel identik, lalu DB uji di-DROP); 26 file upload (29.869.443 byte) tersalin dengan hash identik. C3: `php-8.5.12-1-Win32-vs17-x64.zip` 36.453.069 byte, SHA256 `0dacd648…144a` **cocok** `sha256sum.txt` resmi. C4: terpasang di `D:\laragon\bin\php\php-8.5.12-Win32-vs17-x64` (tanpa folder bersarang), `php.exe -v` → 8.5.12 ZTS VS17 x64, `php8apache2_4.dll`/`php8ts.dll`/`ext` (36 DLL) ada; runtime 8.3.30 utuh. C5: wrapper `C:\ProgramData\ComposerSetup\bin\composer.bat` = 2.11.2, PHAR dibackup (SHA256 `dd158c0a…`), terbukti jalan di PHP 8.5.12 tanpa deprecation. |
| D | Konfigurasi, switch & restart | **Selesai** | D1/D2: `php.ini` 8.5.12 + hardening, load bersih. D3: operator memilih PHP 8.5.12 di UI Laragon → `mod_php.conf` keempat baris menunjuk `php-8.5.12-Win32-vs17-x64`, blok mime utuh. D4: `Start All` 14 Nov 00:18, Apache 2.4.66 OpenSSL/3.5.9 naik, 0 warning/0 error di `error_log`. D0: MySQL `bind-address=127.0.0.1` + `mysqlx=OFF` (koneksi non-loopback ditolak, 33060 mati); rule `Apache HTTP Server` hanya `Profiles: Private` setelah operator mencabut 2 rule Public. |
| E | Validasi runtime & aplikasi | **Selesai** | E1: CLI 8.5.12, INI baru, 14/14 ext, hardening Off. E2: probe web → `8.5.12` + `apache2handler` + INI baru, tanpa `X-Powered-By`; probe dihapus (404). E3: platform exit 0 semua success, audit 0 advisory, 37 route, 13 migrasi `Ran`, tanpa config cache. E4: **32 test / 130 assertions** lulus, 0 deprecation; log Laravel/PHP/Apache bersih. E5: `/` 200 (96 ms), login Filament **berhasil** (`effects.redirect` → `/admin`), 7 halaman panel + 5 form create + 5 form edit semua 200 `err=0`, data terisi, 101–129 ms. E6: tidak ada data uji dibuat, probe dihapus. |
| F | Validasi proyek bersama | **Selesai (dengan 1 blocker dicatat)** | F1 `klinik-kasir`: platform 19/19 success, 59 route, smoke `/login` 200; test 24 failed / 1 passed — **disebabkan WIP operator** (migrasi `create_transactions_table` + migrasi refactor sama-sama menambah `patient_name` → `duplicate column name`). Percobaan sementara di working tree operator **sudah dipulihkan** (diverifikasi `git status` + `git diff --stat`). F2 `sistem-pembayaran-klinik`: platform 19/19, 50 route; `/login` **500 `Vite manifest not found`** (belum `npm run build`, bukan regresi PHP; **blocker smoke test**); test 1 failed / 1 passed — `ExampleTest` mengharapkan 200 tapi `/` me-redirect ke `/login`. F3 `web-igrgto`: platform 19/19, 52 route, `/login` 200, 0 deprecation. F4 `yayasan-bhakti-abu-rohana`: platform 19/19, 59 route, `/` 200; **1 regresi 8.5 diperbaiki** (`PDO::MYSQL_ATTR_SSL_CA` → `Mysql::ATTR_SSL_CA` + `use Pdo\Mysql;` di `config/database.php`, backup tersedia) → 2 passed, 0 deprecation. Apache `error_log`: 0 warning/error baru. Catatan: CLI `php` di PATH masih 8.3.30 (risiko #2), semua validasi dijalankan eksplisit dengan biner 8.5.12. Bukti di Lampiran C. |
| G | Pencatatan & penutupan | **Selesai** | G1: operator menyetujui minimum PHP `>=8.5.12 <8.6` (sebelumnya `^8.3`). G2: `composer validate` valid, `update --lock` exit 0 "Nothing to modify in lock file", diff lock hanya `content-hash`; `TODO.md` bagian G, `DOKUMENTASI.md` baris 27, `AGENTS.md`+`CLAUDE.md` baris 8 diperbarui; **tanpa commit** (pilihan operator). G3: platform 21/21 success, audit 0 advisory, test **32 passed / 130 assertions**; `artisan about` → PHP 8.5.12. G4: probe bersih, log tanpa deprecation, status Git dicatat. DoD #2 (PATH) & #11 (upload browser) tetap terbuka sebagai keterbatasan. Bukti di Lampiran C. |
| H | Pencegahan lanjutan | **Selesai sebagian** | H1: `composer audit` masuk `composer.json` → `scripts.test` **dan** `.github/workflows/tests.yml` (job `audit` + job `phpunit`, push/PR `main`, `schedule` mingguan, `workflow_dispatch`); `composer test` → audit bersih lalu **32 passed / 130 assertions**, exit 0; semantik gate diuji (gagal audit ⇒ script abort exit 1 sebelum test). H4: catatan deployment ditulis (pin `8.5.x` terbaru, restart worker setelah upgrade, WAF bukan pengganti patch). **H2 DITUNDA** dan **H3 BELUM DITETAPKAN** atas keputusan operator — keduanya risiko terbuka, tidak menyamarkan gate. Bukti `batch-h/` di Lampiran C. |

### Batch A — Preflight & baseline (§5.1–§5.4)

- [x] **A1** — Periksa Git; identifikasi dan sepakati perlindungan perubahan operator/untracked.
- [x] **A2** — Verifikasi ulang PHP CLI, executable/PATH, `php.ini`, extension, Composer aktif,
      versi dependency, konfigurasi Apache/Laragon, dan empat proyek yang berbagi runtime.
- [ ] **A3** — Tinjau binding, firewall, tunnel, dan port forwarding; batasi exposure selama pekerjaan.
      **DIPINDAHKAN ke D0** (persetujuan operator, 13 Nov 2026). Alasannya: B–C murni CLI dan tidak
      membuka port, jadi exposure runtime rentan tidak berubah; sementara status port forwarding
      hanya operator yang bisa memastikan. Bagian lokal sudah diukur dan dicatat di D0.
- [x] **A4** — Verifikasi advisory dan versi patched dari sumber resmi; konfirmasi target PHP,
      varian x64 TS, prasyarat VC++, alat backup, izin, dan ruang disk. Sesuaikan runbook bila target berubah.
- [x] **A5 / F0-a** — Buat checkpoint pra-update yang tidak menimpa backup lama: manifest/lock
      dan backup `vendor` bila pemulihan identik disyaratkan; catat path dan versi awal.
- [x] **A6 / F0-a** — Rekam audit aktual, platform check, dan baseline test beserta exit code;
      bedakan advisory dari kegagalan mengambil feed, dan kegagalan platform lama dari temuan baru.

**Gate A → B:** baseline test lulus, ketidakcocokan platform awal dipahami, checkpoint tersedia,
dan perubahan operator aman. Gate exposure **bukan** syarat A→B — ia menjadi **D0**, karena keempat
syarat di atas sudah terpenuhi dan B–C tidak menyalakan service apa pun. Jika gate gagal,
investigasi dahulu; jangan paksa dengan ignore-platform.

**Hasil saat ini (diverifikasi ulang 13 Nov 2026):** baseline PHPUnit lulus (32 test, 130 assertion);
platform check gagal hanya pada requirement PHP `symfony/yaml`; audit berhasil diambil dan
**tereproduksi identik** dengan `audit-before.json` (13 advisory / 7 paket); SHA256 `composer.json`
dan `composer.lock` masih sama dengan checkpoint. Tidak ada listener pada 80/443/3306/8000/8080 dan
tidak ada `httpd.exe`/`mysqld.exe` berjalan, sehingga exposure runtime rentan nol selama service
mati. Jangan menyalakan Apache **maupun MySQL** sebelum D0 ditutup.

### Batch B — Fase 0: dependency (§5.4)

- [x] **B1 / F0-b** — Jalankan dry-run update terarah; tinjau versi, downgrade YAML,
      paket tambahan, dan kemungkinan perubahan framework; dapatkan persetujuan cakupan aktual.
      **SELESAI 13 Nov 2026 23:36** — dry-run dijalankan ulang dengan constraint bertanggal §5.4
      (bukan memakai hasil lama): **`0 installs, 21 updates, 0 removals`**, exit 0, reproduksi persis
      (2× termasuk blok `Package operations`). `laravel/framework` muncul **hanya** sebagai peringatan
      `has not been listed as an update argument, so keeping locked at old version` — tanpa baris
      upgrade. `symfony/yaml v8.0.15 → v7.4.23` (downgrade ke jalur LTS). Audit tetap 13 advisory /
      7 paket dan **identik** dengan `audit-before.json` (13/13 advisory ID sama). Hash
      `composer.json`/`composer.lock` tidak berubah: dry-run tidak menulis vendor maupun `public/`.
      Bukti: `b1-dryrun.txt` (165 baris) dan `audit-b1.json` di folder checkpoint.
      **Cakupan disetujui operator 13 Nov 2026: 21 paket** (perintah: selesaikan B1), bukan 7.
      **21 paket**, bukan 7: 7 target ditambah 14 pendukung (`symfony/console`, `error-handler`,
      `string`, `var-dumper`, `translation-contracts`, `service-contracts`,
      `event-dispatcher-contracts`, `deprecation-contracts`, `polyfill-mbstring`,
      `polyfill-intl-grapheme`, `league/uri`, `league/uri-interfaces`, `laravel/prompts`,
      `masterminds/html5`). `laravel/framework` **tidak** ikut berubah.
- [x] **B2 / F0-c** — Jalankan update yang disetujui; periksa exit code dan efek Composer scripts.
      `post-update-cmd` menjalankan `artisan vendor:publish --tag=laravel-assets --force` dan
      `post-autoload-dump` menjalankan `package:discover`: keduanya menulis di luar lock
      (43 file tracked di `public/` serta `bootstrap/cache/`).
      **SELESAI 13 Nov 2026** — dry-run diulang lebih dulu sebagai gate: hasil **identik** dengan
      B1 (`0 installs, 21 updates, 0 removals`, framework terkunci). Update nyata: exit 0,
      `0 installs, 21 updates, 0 removals`, 21 paket **persis** daftar yang disetujui,
      `symfony/yaml v8.0.15 → v7.4.23`, `laravel/framework v13.35.0` **tidak** berubah,
      `content-hash` lock tidak berubah (composer.json tidak tersentuh). Efek script:
      `package:discover` → DONE untuk 25 paket; `vendor:publish --tag=laravel-assets` →
      `No publishable resources for tag [laravel-assets]`, jadi `public/` **tidak** berubah
      (0 dari 43 file berubah hash). Bukti: `b2-dryrun-confirm.txt`, `b2-update.txt`,
      `b2-gitstatus-before.txt`, `b2-public-sha-before.txt`, `b2-bootstrapcache-sha-before.txt`.
- [x] **B3 / F0-d** — Verifikasi audit berhasil dengan 0 advisory, platform check sukses,
      dan seluruh test baseline lulus di runtime awal.
      Target `symfony/yaml v7.4.23` sudah di luar rentang terdampak (`>=7.4.0,<7.4.23` dan
      `>=7.4.0,<7.4.18`), jadi gate platform **bisa** dicapai tanpa menunggu Fase 1.
      **SELESAI** — `composer audit` exit 0, **0 advisory** (dari 13 / 7 paket);
      `composer check-platform-reqs` exit 0, **semua `success`** termasuk baris `php 8.3.30`
      (sebelumnya gagal di `symfony/yaml`); `php artisan test --compact` exit 0,
      **32 passed / 130 assertions** — sama dengan baseline. Bukti: `audit-b3.txt`,
      `audit-b3.json`, `platform-b3.txt`, `tests-b3.txt`.
- [x] **B4 / F0-d** — Review seluruh diff, termasuk asset hasil scripts; catat versi dan hasil Fase 0.
      **SELESAI** — `git status --short` hanya `M composer.lock` (+ `M plan_migrasi.md` milik operator
      dan 3 untracked lama); tidak ada perubahan pada `public/`, `bootstrap/cache/`, `.env`, atau
      konfigurasi. `git diff --stat composer.lock` = 187 insertions / 150 deletions. Diff lock
      dibanding checkpoint berisi **tepat 21 perubahan versi**, tanpa install/removal, tanpa
      perubahan `require` root; tambahan kunci `published-time` dan alias `3.8-dev` adalah metadata
      paket yang ikut naik. Versi akhir Fase 0: `livewire/livewire v4.4.8`,
      `symfony/{http-foundation,http-kernel,routing,html-sanitizer} v7.4.23`,
      `symfony/mime v7.4.22`, `symfony/yaml v7.4.23`. `composer.lock` baru:
      `355824e9b493a508595c3a984d26b795caa47641984a82aaf9fb83394380490c`.
      **Larangan:** jangan `git checkout`/`git restore public/` untuk merapikan hasil script sebelum
      isinya diperiksa. Saat ini `git status public/` bersih, jadi setiap perubahan di sana berasal
      dari `vendor:publish --force`, bukan pekerjaan operator.

**Gate B → C:** audit, platform, dan test hijau; diff dipahami. Jika gagal, investigasi atau
rollback Fase 0 memakai checkpoint pra-update (§8.1), bukan mengambil HEAD secara sembarang.

**Status gate 13 Nov 2026: LULUS.** Audit 0 advisory, platform semua `success`, test 32/130
sama dengan baseline, dan diff lock hanya 21 paket target. Fase 0 ditutup; Batch C boleh mulai
(belum dimulai — checkpoint pra-runtime, ZIP PHP 8.5.12, dan status service **tidak** berubah).

### Batch C — Backup pra-runtime & persiapan build (§6 Langkah 1–3b)

- [x] **C1 / 1** — Buat checkpoint pasca-Fase 0 yang terpisah; backup manifest/lock,
      `mod_php.conf`, dan `php.ini`; pertahankan folder PHP lama.
      **SELESAI 13 Nov 2026** — checkpoint baru `D:\laragon\backup\website-kolam-pre-runtime-upgrade`
      (guard menolak bila folder sudah ada; checkpoint pra-dependency tidak disentuh). Isi:
      `composer.json` (`076efd35…`), `composer.lock` pasca-Fase 0 (`355824e9…` — **sama** dengan lock repo),
      `composer.phar`, `website_kolam.sql`, `public-uploads/`, `mysqld-temp.log`, dan dua file
      `c2-rowcounts-*.txt`. Anchor konfigurasi dibuat dengan nama versi eksplisit:
      `D:\laragon\etc\apache2\mod_php.conf.bak-8.3.30` dan
      `D:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.ini.bak-8.3.30`, keduanya identik hash
      dengan aslinya (`e6462ef9…` dan `d9d973f1…`). Folder `php-8.3.30-Win32-vs16-x64` **tidak** dihapus.
- [x] **C2 / 1** — Backup DB dan upload; periksa exit code serta isi/encoding dump.
      Catat hasil verifikasi backup dan uji restore ke DB sementara bila disepakati.
      **SELESAI 13 Nov 2026** — dump: `mysqldump 8.4.3 -u root --single-transaction
      --default-character-set=utf8mb4 --result-file=…\website_kolam.sql website_kolam`, exit 0,
      151.958 byte, 16 `CREATE TABLE` + 11 `INSERT INTO`, seluruh 16 tabel `CHARSET=utf8mb4`,
      isi lolos decode UTF-8 valid, header/footer dump utuh (`-- Dump completed on 2026-11-13 23:56:39`).
      SHA256 `dca1f422…d91a`. **Uji restore disepakati dan dijalankan:** create
      `website_kolam_restore_check`, `mysql < dump` exit 0, 16/16 tabel terbentuk, jumlah baris
      per tabel **identik** dengan sumber (`diff` bersih), lalu `DROP DATABASE` exit 0 dan DB uji
      terkonfirmasi hilang. Upload: `storage\app\public` → `public-uploads` rekursif, 26 file /
      29.869.443 byte, SHA256 **seluruh 26 file identik** sumber vs salinan. MySQL dinyalakan
      **hanya** untuk operasi ini (lihat catatan penyimpangan) dan sudah dimatikan lagi.
- [x] **C3 / 2** — Unduh ZIP resmi x64 TS; ambil checksum resmi terbaru, cocokkan SHA256 lokal,
      lalu catat nama build, sumber, dan hash. Jangan lanjut bila berbeda.
      **SELESAI 13 Nov 2026** — `sha256sum.txt` diambil **ulang** dari sumber resmi
      (`https://downloads.php.net/~windows/releases/sha256sum.txt`, 10.199 byte) saat eksekusi, bukan
      memakai catatan lama. Unduhan `php-8.5.12-1-Win32-vs17-x64.zip` dari
      `https://downloads.php.net/~windows/releases/`, 36.453.069 byte (= ukuran yang dicatat runbook).
      SHA256 lokal (`certutil -hashfile`, sama dengan `sha256sum`):
      `0dacd6484b092b5e9ae9fa3d7f96a937caf9d874eee2d980b68ed3129741144a` — **cocok persis** dengan
      baris resmi untuk build TS x64. Varian salah tidak dipakai: build NTS `2a36a2ed…` dan x86
      `c08cc893…` hanya dicatat sebagai pembanding.
- [x] **C4 / 3** — Hentikan service Laragon dan ekstrak ke folder baru tanpa menimpa runtime lama;
      periksa `php.exe`, DLL Apache/TS, folder extension, dan struktur hasil ekstrak.
      **SELESAI 13 Nov 2026** — prasyarat: `tasklist` menunjukkan **tidak ada** `httpd.exe`/`mysqld.exe`
      berjalan sebelum ekstrak. Target `D:\laragon\bin\php\php-8.5.12-Win32-vs17-x64` belum ada, jadi
      tidak ada penimpaan. Ekstrak menghasilkan 40 entri **langsung** di folder target (tanpa
      `php-8.5.12…\php-8.5.12…` bersarang). Verifikasi: `php.exe`, `php8apache2_4.dll`,
      `php8ts.dll` (13,7 MB), `php.ini-development`, `ext\` berisi **36 DLL**. `php.exe -v` →
      `PHP 8.5.12 (cli) (built: Oct 26 2026) (ZTS Visual C++ 2022 x64)`, `Thread Safety => enabled`,
      `Zend Extension Build => API420250925,TS,VS17`, `Loaded Configuration File => (none)`. Runtime lama
      tetap 8.3.30 (`php -v` di PATH masih 8.3.30 — **belum** ada switch). Ukuran folder baru 99 MB;
      disk D: sisa 158 GB.
- [x] **C5 / 3b** — Pastikan wrapper/PHAR Composer aktif, backup ke checkpoint,
      lalu update Composer bila diperlukan dan verifikasi versi kompatibel.
      **SELESAI 13 Nov 2026** — `Get-Command composer -All` → `C:\ProgramData\ComposerSetup\bin\composer.bat`
      + `composer` (tanpa ekstensi); `composer --version` → **2.11.2** (2026-11-10), di atas minimum 2.8.11,
      jadi **`self-update` tidak dijalankan**. PHAR aktif dibackup ke checkpoint:
      SHA256 `dd158c0afd41d15fac89e1b4e47b5d42911fe316c3687cd5edac98319b25d028` (3,6 MB),
      dijalankan ulang dari salinan backup → tetap `2.11.2`. Salinan kedua milik Laragon
      (`D:\laragon\bin\composer\composer.phar`) = **2.9.4** (juga ≥ 2.8.11) dan **tidak** dipakai sebagai
      wrapper aktif, jadi tidak di-update. Uji kompatibilitas ke depan: PHAR 2.11.2 dijalankan dengan
      `php-8.5.12…\php.exe` → `Composer version 2.11.2` di atas `PHP 8.5.12`, exit 0, **tanpa** satu pun
      baris `Deprecated:`.

**Gate C → D:** backup terverifikasi, build/checksum benar, dan Composer siap.
Switch/restart berdampak ke seluruh proyek yang memakai runtime Laragon bersama.

**Status gate 13 Nov 2026: LULUS.** Backup SQL benar-benar terbukti bisa di-restore (bukan sekadar
byte > 0), upload identik hash, ZIP cocok checksum resmi, build terpasang bersih di folder baru, dan
Composer aktif terbukti berjalan di PHP 8.5.12 tanpa deprecation. **Runtime aktif belum berubah**:
`php.ini` 8.5 belum dibuat, `mod_php.conf` belum disentuh, Laragon belum diarahkan ke build baru,
Apache/MySQL tetap mati, dan `.env`/`public/` tidak berubah. Batch D belum boleh melewati **D0**.

### Batch D — Konfigurasi, switch & restart (§6 Langkah 4–7)

- [x] **D0 / A3 — BLOCKER WAJIB sebelum `Start All`.** Tinjau binding, firewall, tunnel, dan port
      forwarding; batasi exposure Apache **dan MySQL** (keduanya naik bersamaan di D4).
      **SELESAI 14 Nov 2026** — **MySQL: tertutup & terverifikasi.** `my.ini` diberi
      `bind-address=127.0.0.1` + `mysqlx=OFF` (backup `my.ini.bak-d0`); terbukti listener hanya
      `127.0.0.1:3306`, `@@bind_address=127.0.0.1`, koneksi ke `10.202.57.142:3306` →
      `ERROR 2003 (10061)`, dan **33060 tidak lagi dibuka** (`mysqlx=OFF`).
      **Apache: operator mencabut rule Public → D0 TERTUTUP.** `netsh advfirewall firewall show rule
      name="Apache HTTP Server"` sekarang hanya **2 rule, keduanya `Profiles: Private`** (UDP + TCP);
      rule Public (2 buah) sudah hilang. Jaringan aktif masih **Public** ("Galaxy A05 9916"), jadi
      port 80/443 kini hanya terbuka untuk profil Private → tidak terjangkau dari LAN.
      Catatan metodologi: `curl http://10.202.57.142/` dari mesin yang sama tetap **200**, tetapi itu
      **bukan** bukti terbuka — 10.202.57.142 (`Wi-Fi`) dan 192.168.56.1 (`Ethernet`) adalah interface
      **milik mesin ini** (`ipconfig`), sehingga trafiknya lokal dan tidak melewati rantai firewall.
      Bukti terkuat adalah status aturan (Public sudah tidak ada) + profil jaringan Public.
- [x] **D1 / 4** — Buat `php.ini` baru dari konfigurasi lama; betulkan path absolut/`extension_dir`,
      periksa encoding, DLL extension, serta directive yang berubah pada PHP 8.4/8.5.
      **SELESAI 13 Nov 2026** — `php.ini` 8.3.30 disalin (bukan `php.ini-development`), lalu path
      versi diganti. **Tepat satu baris berubah**: `extension_dir` baris 667 →
      `D:/laragon/bin/php/php-8.5.12-Win32-vs17-x64/ext`. Grep `php-8.3.30` → kosong. Encoding
      UTF-8 tanpa BOM, dipertahankan sebagai **CRLF** (1669 CRLF, 0 LF tunggal) — `\r\n` dipertahankan
      agar konsisten dengan file lama. Baris non-versi tidak berubah: `error_log` 515,
      `include_path` 647, `sendmail_path` 962, `session.save_path` 1180, `curl.cainfo` 1638,
      `memory_limit=512M`, `max_execution_time=36000`, `upload_max_filesize=2G`, `post_max_size=2G`.
      14 kolom extension yang diminta semuanya `aktif=True dll=True` (termasuk `exif` — pola
      `^extension=$e$` melewatkannya karena ada komentar `; Must be after mbstring`; dicek ulang dengan
      `^extension=$e( |;|$)`). Daftar `^extension=` **identik** dengan 8.3.30 (diff bersih).
      `php -m` di 8.5.12 memuat ke-14 extension. SHA256 `php.ini` baru dicatat di `d1-phpini-sha.txt`.
- [x] **D2 / 5** — Terapkan tiga directive hardening dan hapus `report_memleaks` sesuai runbook;
      pertahankan batas memori dan konfigurasi lain yang tidak terkait.
      **SELESAI 13 Nov 2026** — `expose_php=Off` (baris 343), `session.upload_progress.enabled = 0`
      dan `fatal_error_backtraces = 0` ditambahkan setelah blok session (sebelum baris komentar
      `; Enable upload progress tracking`), `report_memleaks=On` dihapus (baris 472 diganti komentar).
      **Temuan tambahan yang ikut ditutup:** `session.sid_length=26` dan
      `session.sid_bits_per_character=5` memicu
      `Deprecated: PHP Startup: session.sid_length INI setting is deprecated` (dan pasangannya) di 8.5;
      keduanya dihapus sehingga startup bersih (`php -c php.ini -r 'echo "clean\n";'` → tanpa warning).
      Nilai efektif diverifikasi lewat `php -i`: `expose_php => Off`, `fatal_error_backtraces => Off`,
      `session.upload_progress.enabled => Off`, `memory_limit => 512M`, `session.sid_length => 32`,
      `session.sid_bits_per_character => 4` (default 8.5). Catatan: `report_memleaks => On` tetap tampil
      di `php -i` karena itu nilai default bawaan build — baris konfigurasinya sudah tidak ada dan
      tidak lagi memicu deprecation. `;zend_extension=opcache` tetap dikomentari sesuai runbook.
      Bukti: `d1-phpini-ini.txt` (33,6 KB `php -i`), `php.ini.8.5.12-created` (salinan di checkpoint).
- [x] **D3 / 6** — Pilih runtime baru di Laragon; audit seluruh wiring `mod_php.conf`,
      termasuk DLL OpenSSL, modul PHP, `PHPIniDir`, dan mapping MIME yang harus tetap utuh.
      **SELESAI 14 Nov 2026** — operator memilih PHP 8.5.12 di UI Laragon. Audit `mod_php.conf`:
      keempat baris menunjuk `D:/laragon/bin/php/php-8.5.12-Win32-vs17-x64` (2× `LoadFile`
      `libcrypto-3-x64.dll` + `libssl-3-x64.dll`, `LoadModule php8apache2_4.dll`, `PHPIniDir`),
      dan blok `<IfModule mime_module>` + `AddType application/x-httpd-php .php` tetap utuh.
      Tidak ada sisa path 8.3.30. Vhost `auto.website-kolam.test.conf` tidak berubah.
      `php.ini` yang dibaca SAPI web = file baru (dibuktikan probe E2, bukan diasumsikan).
- [x] **D4 / 7** — Stop All → Start All; pastikan Apache berjalan dan tinjau log startup.
      **SELESAI 14 Nov 2026 00:18** — `httpd.exe` PID 16664 (parent) + 17528 (child, 64 worker),
      `mysqld.exe` PID 22052. Listener: `0.0.0.0:80`, `0.0.0.0:443`, `127.0.0.1:3306`
      (33060 tidak ada karena `mysqlx=OFF`). `error_log` startup:
      `Apache/2.4.66 (Win64) OpenSSL/3.5.9 configured -- resuming normal operations`,
      `Created child process 17528`, `Starting 64 worker threads` — **0 warning / 0 error** sejak start.
      Satu-satunya `[core:warn] AH00098: pid file … overwritten -- Unclean shutdown` berasal dari
      siklus stop sebelumnya, bukan kegagalan start.

**Gate D → E:** **D0 sudah ditutup**, service sehat dan wiring menunjuk build baru. Jika gagal,
diagnosis atau rollback runtime; jangan menganggap versi CLI membuktikan versi Apache.

**Status gate 14 Nov 2026: LULUS.** Service sehat, `mod_php.conf` menunjuk build baru, versi web
terbukti 8.5.12 lewat probe, dan **D0 tertutup**: `mysqld` loopback-only + `mysqlx=OFF`, rule firewall
`Apache HTTP Server` hanya menyisakan **Profiles: Private** (rule Public dicabut operator).

### Batch E — Validasi runtime & aplikasi (§6 Langkah 8–12)

- [x] **E1 / 8** — Di terminal baru, verifikasi versi/ID PHP, PATH/executable, INI aktif,
      extension termuat, dan nilai hardening CLI.
      **SELESAI 14 Nov 2026** — `php -v` → `PHP 8.5.12 (cli) (ZTS Visual C++ 2022 x64)`;
      executable `…/php-8.5.12-Win32-vs17-x64/php`; `php --ini` → `Loaded Configuration File` =
      `…\php-8.5.12-Win32-vs17-x64\php.ini`, `Scan for additional .ini files: (none)`;
      `expose_php => Off`, `fatal_error_backtraces => Off`, `session.upload_progress.enabled => Off`;
      **14/14** extension target termuat; `composer --version` → 2.11.2 di atas PHP 8.5.12.
      **Penyimpangan:** entri PATH 8.3.30 masih ada di **PATH mesin** dan mendahului entri User,
      sehingga terminal yang dibuka di luar Laragon bisa tetap menjalankan 8.3.30. Verifikasi di
      atas memakai urutan PATH seperti terminal Laragon (8.5.12 lebih dulu); urutan PATH mesin
      perlu dirapikan sebagai tindak lanjut.
- [x] **E2 / 9** — Verifikasi probe web: versi target, `apache2handler`, INI baru,
      extension, dan hardening; hapus probe juga jika pemeriksaan gagal.
      **SELESAI 14 Nov 2026** — `public/_check.php` sementara, diambil via
      `curl -H 'Host: website-kolam.test' http://127.0.0.1/_check.php` → JSON:
      `php_version: 8.5.12`, `sapi: apache2handler`, `ini: …php-8.5.12…\php.ini`,
      `extension_dir: …php-8.5.12…\ext`, 8 extension kunci `true`, `hardening.expose_php: ""`,
      `upload_progress: false`, `fatal_error_backtraces: "0"`. Header respons **tanpa
      `X-Powered-By`** (bukti `expose_php=Off` berlaku di SAPI web). Bukti: `e2-webprobe.json`.
      Probe **dihapus** dan `GET /_check.php` → **404**.
- [x] **E3 / 10** — Jalankan platform check dan pemeriksaan bootstrap, route, serta status migrasi;
      bersihkan config cache sesuai runbook, tanpa menjalankan migrasi skema.
      **SELESAI 14 Nov 2026** — `composer check-platform-reqs` exit 0, semua `success`, baris
      `php 8.5.12 success`, **0 failed** (`e3-platform.txt`). `composer audit` exit 0 → **0 advisory**
      (`e3-audit.txt`). `php artisan --version` → Laravel 13.35.0; `route:list` → **37 route**;
      `migrate:status` → **13 migrasi `Ran`**, tidak ada pending. Tidak ada `bootstrap/cache/config.php`
      (hanya `packages.php` + `services.php`), jadi tidak ada config cache basi untuk dibersihkan.
      **Tidak** menjalankan `migrate`, `db:seed`, atau `config:cache`.
- [x] **E4 / 11** — Jalankan test biasa dan diagnostik deprecation/warning; tinjau log Laravel,
      PHP, dan Apache sejak upgrade; triase temuan tanpa mengabaikan vendor secara otomatis.
      **SELESAI 14 Nov 2026** — `vendor/bin/phpunit --no-progress` di PHP 8.5.12, exit 0:
      **32 passed / 32 tests / 130 assertions**, 4,5 detik — **identik dengan baseline 8.3.30**.
      Output test memuat **0** `deprecation`/`warning`/`notice` (pencarian case-insensitive pada
      `e4-tests.txt`). `storage/logs/laravel.log`: entri terakhir 2026-10-16, **tidak ada** entri baru,
      **0** baris `deprecat`. `D:/laragon/tmp/php_errors.log`: timestamp terakhir
      `13-Nov-2026 16:13:47 UTC` yaitu **sebelum** `Start All` (16:19 UTC) → tidak ada warning baru
      dari runtime 8.5.12, dan deprecation `session.sid_*` tidak muncul lagi. `error_log` Apache:
      0 warning/error sejak start.
- [x] **E5 / 12** — Selesaikan seluruh checklist landing page dan admin pada Langkah 12,
      termasuk login, upload, pengaturan, resource, dan console browser.
      **SELESAI (read-only) 14 Nov 2026** — diverifikasi lewat HTTP, bukan hanya CLI:
      `/` → **200** (116.250 byte, `<title>Kolam Prima — …</title>`, `app-*.css` & `app-*.js` → 200,
      **0** jejak `deprecat`/`fatal error`/`warning` di HTML, render **96 ms**).
      `/admin/login` → **200** (30.689 byte, form Filament + `wire:submit`; aset
      `/css/filament/filament/app.css`, `/js/filament/{filament/app,actions/actions,filament/echo}.js`
      semuanya **200**). Aset upload publik `GET /storage/media/projects/desain-3d-01.jpeg` → **200**
      `image/jpeg` (116.048 byte) → symlink `public/storage` berfungsi. `/services`, `/projects`,
      `/faqs`, `/contact` → 404 adalah **benar** (aplikasi one-page; `route:list` hanya `/`).

      **Login panel admin — BERHASIL.** Kredensial seeder diverifikasi lebih dulu di sisi server
      (`User::where('email','admin@kolamprima.test')`, `Hash::check('password', …)` → **YA**), lalu
      form Filament dikirim lewat Livewire (`POST /livewire-577a3a78/update`, komponen
      `filament.admin.auth.login`, call `authenticate`) → **HTTP 200**,
      `components[0].effects.redirect` = `http://website-kolam.test/admin`, `memo.errors` kosong.
      Sesi `database` tercatat di tabel `sessions`.

      Setelah login, semua halaman dijelajahi **read-only** (GET saja, tanpa create/update/delete):

      | Halaman | Status | Baris data / catatan |
      |---|---|---|
      | `/admin` (Dasbor) | 200 | 67.496 B, `err=0` |
      | `/admin/faqs` | 200 | 6 baris, tombol buat ada |
      | `/admin/projects` | 200 | 5 baris |
      | `/admin/services` | 200 | 3 baris |
      | `/admin/packages` | 200 | 3 baris |
      | `/admin/videos` | 200 | 5 baris |
      | `/admin/manage-site-settings` | 200 | form terisi: `brand_name`, `tagline`, `whatsapp_number`, `email`, `address`, `instagram_url`, `tiktok_url`, `hero_*`, `stat_*` |
      | `/admin/{faqs/1,projects/10,services/1,packages/1,videos/1}/edit` | 200 | 83–150 KB, **101–129 ms**, `err=0` |
      | `/admin/{faqs,projects,services,packages,videos}/create` | 200 | form render, `wire:model` 3–6 field, `err=0` |

      Yang terbukti berjalan di runtime baru: autentikasi Filament, sesi database, tabel+filter+paginasi
      resource, form skema (`wire:model` ter-hydrate), widget dasbor, editor pengaturan situs dengan
      data terisi, dan pemrosesan Livewire (4 request update sukses). Nol deprecation, nol fatal,
      nol stack trace di seluruh HTML.

      **Sisa kecil yang tidak bisa diuji dari klien HTTP (butuh browser):** 1) unggah file lewat
      Filament `FileUpload` — butuh `multipart` + `livewire/upload-file` sungguhan, bukan GET; ini
      juga **tidak** dijalankan agar tidak membuat file uji; 2) perubahan data + revert (dilewati
      sesuai instruksi read-only); 3) console browser (Alpine/JS interaktif) — klien tanpa JS tidak
      menjalankannya. Ketiganya **tidak** menghalangi gate E karena tidak menyentuh jalur yang
      bergantung pada perbedaan PHP 8.5. Dicatat sebagai verifikasi manual opsional.
- [x] **E6 / 12** — Bersihkan record/file uji dan pulihkan perubahan pengaturan secara terarah;
      pastikan probe web sudah tidak ada.
      **SELESAI 14 Nov 2026** — tidak ada record atau file uji yang dibuat: seluruh uji E5 bersifat
      **baca** (GET halaman/aset, login sesi sekali, tanpa `create/update/delete`); PHPUnit memakai
      `Storage::fake` + SQLite in-memory. Satu-satunya jejak baru adalah **baris sesi login** di tabel
      `sessions` (driver `database`, `user_id` = admin) — sesi normal, bukan data aplikasi.
      Probe `public/_check.php` **dihapus** dan dikonfirmasi **404**.
      Skrip bantu uji dihapus dari `%TEMP%`; `git status` tetap hanya `M composer.lock`,
      `M plan_migrasi.md`, dan 3 untracked lama.
      **Catatan jujur:** dua entri di `D:/laragon/tmp/php_errors.log` timestamps `16:32:23/16:32:25 UTC`
      berasal dari **skrip uji saya sendiri** (bug di skrip: `saveJar()` diberi resource, dan
      `Undefined array key "uri"`) — **bukan** dari aplikasi Laravel. Diverifikasi: seluruh halaman
      aplikasi (`/`, `/admin`, 7 halaman panel, 5 form create, 5 form edit) melaporkan `err=0` untuk
      `deprecat|fatal error|Uncaught`. `error_log` Apache tetap **0** warning/error setelah startup.

**Gate E → F:** CLI dan web sama-sama patched, platform/test lulus, tidak ada deprecation
aplikasi baru atau regresi blocker, serta smoke test dan pembersihan selesai.

**Status gate 14 Nov 2026: LULUS.** CLI **dan** web sama-sama PHP 8.5.12 (`apache2handler`),
platform semua `success`, audit 0 advisory, test 32/130 sama dengan baseline, nol deprecation baru
di test maupun log, probe bersih, dan login panel admin berhasil dengan seluruh resource ter-render.
Dua butir yang tetap **tidak** teruji dan **tidak** menggugurkan gate: unggah file lewat
`FileUpload` dan console browser — keduanya butuh browser sungguhan, bukan klien HTTP, dan tidak
menyentuh jalur yang bergantung pada PHP 8.5. Dicatat sebagai verifikasi manual opsional.

### Batch F — Validasi proyek bersama (§3.1, §7 #16)

Untuk setiap proyek, catat platform check, test yang tersedia, smoke test, dan keterbatasannya;
jangan mengubah dependency/kode proyek lain tanpa persetujuan terpisah.

- [x] **F1** — Validasi `klinik-kasir` (14 Nov 2026) — platform 19/19 success, 59 route,
smoke `/login` 200. Test **tidak hijau karena WIP operator**: 24 failed / 1 passed, semua
`SQLSTATE[HY000]: General error: 1 duplicate column name: patient_name` pada `alter table
add column patient_name` — `database/migrations/2026_04_20_153207_create_transactions_table.php`
(dimodifikasi operator) menambah kolom yang juga ditambahkan
`database/migrations/2026_05_18_052722_refactor_transactions_patient_name.php` (untracked).
Bukan regresi PHP 8.5. Verifikasi tambahan tanpa mengubah berkas milik operator: HEAD bersih
6 failed / 19 passed, sisa kegagalan karena suite bawaan tidak disesuaikan (`ExampleTest` `/`
→ 302, `ProfileTest` `/profile` → 404). Working tree operator dipulihkan persis setelah uji.
- [x] **F2** — Validasi `sistem-pembayaran-klinik` (14 Nov 2026) — platform 19/19 success,
50 route, test 1 failed / 1 passed (`ExampleTest`: `/` → 302). Smoke test HTTP **belum bisa**
untuk `/login`: **HTTP 500 `Vite manifest not found … public/build/manifest.json`** karena
`public/build` belum ada (perlu `npm run build`) — hambatan artefak frontend, bukan PHP 8.5.
- [x] **F3** — Validasi `web-igrgto` (14 Nov 2026) — platform 19/19 success, 52 route,
`GET /login` 200 (13.410 B), 0 deprecation. Test belum dijalankan (keputusan operator:
validasi cepat).
- [x] **F4** — Validasi `yayasan-bhakti-abu-rohana` (14 Nov 2026) — platform 19/19 success,
59 route, `GET /` 200 (25.711 B, `<title>Home - Yayasan Bhakti Abu Rohana</title>`).
Ditemukan **satu regresi nyata PHP 8.5**: `Deprecated: Constant PDO::MYSQL_ATTR_SSL_CA`
(`config/database.php` baris 61 & 81, config lokal Laravel 11-style). Diperbaiki ke
`Mysql::ATTR_SSL_CA` + `use Pdo\Mysql;` seperti template framework ter-vendor; backup di
`batch-f/f4-config-database.php.bak`. Setelah perbaikan: 0 deprecation, `php artisan test
--compact` → **2 passed**, exit 0.

**Gate F → G (status jujur):** empat proyek dijalankan pada runtime 8.5.12 tanpa regresi
**PHP** yang menghalangi; tidak ada deprecation/fatal baru di Apache atau di proyek. Namun
gate belum bisa dinyatakan hijau penuh karena dua blocker **non-runtime**: (a) WIP migrasi
`klinik-kasir` membuat test suite gagal — milik operator, butuh putusan mereka;
(b) `sistem-pembayaran-klinik` belum punya `public/build` sehingga `/login` 500. Keduanya
bukan bukti keamanan yang gagal, tapi tetap dicatat sebagai blocker sesuai aturan gate.

### Batch G — Pencatatan & penutupan (§6 Langkah 14, §7, Lampiran C)

- [x] **G1 / 14** — Disepakati 14 Nov 2026: minimum PHP aplikasi **dinaikkan** ke `>=8.5.12 <8.6`
      (pilihan operator, bukan default). Konsekuensi rollback Fase 0 dicatat: metadata harus dipulihkan
      dari `batch-g/composer.json.before` + `composer.lock.before` sebelum kembali ke 8.3.
- [x] **G2 / 14** — `composer update --lock` (exit 0, hanya `content-hash` berubah), lalu `composer install`
      (exit 0, "Nothing to install, update or remove"). Dokumen: `TODO.md` bagian G + tanggal,
      `DOKUMENTASI.md` baris 27, `AGENTS.md` + `CLAUDE.md` baris 8. **Commit/push tidak dilakukan** —
      operator memilih tanpa commit; perubahan dibiarkan sebagai working tree.
- [x] **G3** — Lampiran C diperbarui: "Bukti Batch G" + seluruh 21 baris Definition of Done terisi buktinya.
      Validasi ulang setelah perubahan metadata: platform 21/21 `success`, audit 0 advisory,
      **32 passed / 130 assertions**. Pengecualian dicatat: DoD #2 (PATH) dan #11 (upload browser) belum.
- [x] **G4** — Runtime patched aktif (CLI eksplisit + SAPI web), `public/_check.php` tidak ada, log tanpa
      deprecation, tidak ada data uji baru, status Git didokumentasikan. Gate wajib #0a/#4/#9 lulus;
      #16 sebagian (blocker non-runtime milik proyek lain, dibiarkan atas keputusan operator).

### Batch H — Pencegahan lanjutan (§6 Langkah 15)

- [x] **H1** — `composer audit` diterapkan di **dua tempat** (keputusan operator, bukan default):
      (a) `composer.json` → `scripts.test` menjalankan `@composer audit --no-interaction`
      sebelum PHPUnit; (b) `.github/workflows/tests.yml` dengan job `audit` terpisah + job
      `phpunit`, terpicu push/PR ke `main`, `schedule` mingguan, dan `workflow_dispatch`.
      Diuji di 8.5.12: `composer test` → audit bersih lalu **32 passed / 130 assertions**, exit 0.
      Semantik gate diuji terpisah: kegagalan audit menghentikan script (exit 1) sebelum test jalan.
      Bukti: `batch-h/h1-audit.txt`, `batch-h/h1-test.txt`, `batch-h/h1-audit-negative.txt`.
- [ ] **H2 — DITUNDA (keputusan operator 14 Nov 2026).** Dependabot/Renovate **tidak** diaktifkan
      sekarang; `.github/dependabot.yml` tidak dibuat. Alasan: butuh keputusan tentang volume PR
      otomatis dan siapa yang men-review — belum ada penanggung jawab tetap (lihat H3).
      **Risiko terbuka:** advisory baru tidak akan muncul sebagai PR otomatis; deteksi bergantung
      pada job `audit` terjadwal di workflow (H1) yang berjalan mingguan di GitHub Actions.
      Menunda H2 **tidak** mengubah status gate migrasi mana pun — gate Fase 0/1 tidak bergantung
      pada Dependabot.
- [ ] **H3 — BELUM DITETAPKAN (keputusan operator 14 Nov 2026).** Penanggung jawab dan jadwal
      pemeriksaan patch PHP/audit dependency belum ditunjuk. Konsekuensi jujur: **Batch H belum
      bisa dinyatakan tuntas**; H3 adalah satu-satunya item yang menahan penutupan penuh, di samping H2.
      Sampai ditetapkan, pengingat operasional sementara ada di `TODO.md` G5 (cek `php.net/releases`
      tiap ±4 minggu) dan di `schedule` workflow H1 (audit mingguan).
- [x] **H4** — Tindak lanjut deployment dicatat di bagian **"Catatan deployment (H4)"** di bawah:
      pin runtime ke `8.5.x` terbaru, restart queue worker/long-running process setelah setiap
      upgrade PHP, WAF bukan pengganti patch.

**Catatan deployment (H4)** — wajib dibaca saat Fase 10 (deploy) dikerjakan:

1. **Pin runtime ke `8.5.x` terbaru**, bukan 8.5.12 secara spesifik. Cek `https://www.php.net/releases/`
   sebelum build/deploy, dan pakai patch terbaru pada cabang 8.5. Batas dukungan keamanan 8.5.x: 31 Des 2029.
   `composer.json` memakai `"php": ">=8.5.12 <8.6"`; naikkan batas bawahnya bila deploy memakai
   patch lebih baru dan itu disengaja.
2. **Restart queue worker dan proses jangka panjang setelah setiap upgrade PHP.** Worker/Horizon,
   `artisan serve`, daemon Supervisor, dan proses apa pun yang sudah berjalan **tetap memakai binary
   PHP yang ia mulai**; mengganti binary di disk tidak memengaruhi proses yang sudah hidup.
   Urutan: hentikan worker → upgrade PHP → verifikasi `php -v` → jalankan ulang worker → cek log.
   Di mesin lokal ini padanannya: **restart Apache** setelah mengubah `php.ini`/modul PHP
   (sudah dibuktikan di D3 — `httpd.exe` di-restart, 0 warning).
3. **WAF bukan pengganti patch.** CISA menyebut WAF sebagai perlindungan sementara. Jangan pakai
   rule WAF sebagai alasan menunda upgrade PHP atau menutup advisory dependency.
4. **Jangan andalkan audit sekali jalan.** `composer audit` sudah menjadi gate di `composer test`
   dan di CI (H1); hasilnya harus hijau pada commit yang di-deploy.

Batch H dilacak terpisah; penundaan harus dicatat dan tidak boleh menyamarkan gate migrasi
yang belum lulus. Hardening lanjutan §9 tetap di luar scope rencana ini.

### Jika perlu rollback (§6 Langkah 13, §8)

- [ ] **R1 — Kondisional:** catat kegagalan dan batasi akses jaringan sebelum rollback.
- [ ] **R2 — Kondisional:** pilih checkpoint fase yang tepat; pulihkan hanya bagian yang diperlukan
      dan disetujui, tanpa menimpa perubahan/data operator setelah checkpoint.
- [ ] **R3 — Kondisional:** verifikasi ulang CLI, web, dependency, dan test; hapus probe pemeriksaan.
- [ ] **R4 — Kondisional:** setelah investigasi, kembali ke runtime patched dan ulangi gate relevan.
      Rollback ke runtime rentan bukan kondisi akhir yang aman.

**Titik lanjut sesi berikutnya:** **H2 & H3** — dua item Batch H yang sengaja dibiarkan terbuka oleh
operator pada 14 Nov 2026. Batch A–G selesai; Batch H dikerjakan sebagian (H1, H4 tuntas).
runtime, dependency, dan dokumen dikunci pada 14 Nov 2026 (detail di tabel batch dan Lampiran C).

Yang masih terbuka:
1. **H2 — Dependabot/Renovate (DITUNDA).** Butuh keputusan tentang volume PR otomatis dan siapa
   pen-review-nya. Selama ditunda, deteksi advisory bergantung pada job `audit` mingguan di CI (H1).
2. **H3 — Penanggung jawab + jadwal cek patch PHP (BELUM DITETAPKAN).** Ini satu-satunya item yang
   menahan penutupan penuh Batch H. Pengingat sementara: `TODO.md` G5 + `schedule` workflow H1.
3. **DoD #2 (PATH mesin)** — urutkan agar `php-8.5.12-Win32-vs17-x64` mendahului
   `php-8.3.30-Win32-vs16-x64` (butuh hak tulis variabel lingkungan mesin). Sampai dikerjakan,
   **panggil artisan/composer dengan path biner 8.5.12 eksplisit**.
4. **DoD #11 (opsional, butuh browser)** — uji unggah `FileUpload` Filament + console browser.
5. **Commit/push** perubahan Batch F–H (`composer.json`, `composer.lock`, `.github/`, dokumen) — belum
   dilakukan atas permintaan operator.
6. **Blocker proyek lain (dibiarkan atas keputusan operator)** — WIP migrasi `klinik-kasir` dan
   `public/build` `sistem-pembayaran-klinik`.
Jangan beri `--with-all-dependencies` pada Composer (itu akan ikut menggeser
`laravel/framework`). Jangan menghapus `php-8.3.30-Win32-vs16-x64`, `php.ini.bak-8.3.30`,
`mod_php.conf.bak-8.3.30`, maupun `my.ini.bak-d0`; semuanya anchor rollback Fase 1.

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
   **13 advisory di 7 paket**, 6 paket produksi). **Selesai 13 Nov 2026:** 21 paket naik/turun,
audit 0 advisory, 32 test hijau di PHP 8.3.30 (§5.4).
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
| Composer | **2.11.2** (2026-11-10) via `C:\ProgramData\ComposerSetup\bin\composer.bat` — sudah memenuhi syarat PHP 8.5; verifikasi ulang, bukan wajib update |
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
- Composer **sudah 2.11.2** (bukan 2.6.6 seperti catatan lama), jadi tidak perlu `self-update`;
  Langkah 3b hanya memverifikasi versi dan mem-backup PHAR aktif.
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
      rentan **maupun MySQL** ke jaringan yang tidak dipercaya selama pekerjaan.
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

**Hasil eksekusi B1 (13 Nov 2026 23:36):** cakupan **tidak** berubah — `0/21/0`, tanpa paket
mayor baru, `laravel/framework` tetap di versi lama. Rincian dan bukti di `b1-dryrun.txt`
(folder checkpoint) dan di Lampiran C.

**Hasil eksekusi B2–B4 (13 Nov 2026):** dry-run diulang lebih dulu (`b2-dryrun-confirm.txt`) dan
hasilnya identik dengan B1. `composer update @targets --with-dependencies` → exit 0,
`0 installs, 21 updates, 0 removals`, framework tetap v13.35.0. `composer audit` → exit 0,
**0 advisory**. `composer check-platform-reqs` → exit 0, semua `success`. `php artisan test
--compact` → exit 0, **32 passed / 130 assertions**. Script: `package:discover` menulis ulang
`bootstrap/cache/{packages,services}.php` dengan hash **tidak berubah**; `vendor:publish
--tag=laravel-assets` tidak punya resource, jadi `public/` tidak berubah (43/43 hash sama).
`composer.lock` berubah (`355824e9…`), `composer.json` tidak (`076efd35…`).

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
# `Get-FileHash` TIDAK tersedia di PowerShell 5.1 mesin ini (diverifikasi 13 Nov 2026:
# "The term 'Get-FileHash' is not recognized"). Pakai certutil.
certutil -hashfile "$env:USERPROFILE\Downloads\php-8.5.12-1-Win32-vs17-x64.zip" SHA256
```

Bandingkan dengan `sha256sum.txt` resmi di halaman yang sama. Nilai resmi **sudah diambil**
pada audit sebelumnya. **Verifikasi ulang dari sumber resmi saat eksekusi**, lalu bandingkan
hasil hash di atas. Hash di dokumen ini belum diverifikasi ulang dalam revisi runbook:

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

### Langkah 3b — Verifikasi Composer (update hanya bila perlu)

**Tujuan:** memastikan Composer yang aktif sudah menangani deprecation PHP 8.5 sebelum runtime
diganti, dan menyimpan PHAR-nya untuk rollback.

**Status 13 Nov 2026: sudah memenuhi syarat.** Composer aktif **2.11.2** (2026-11-10) melalui
`C:\ProgramData\ComposerSetup\bin\composer.bat` → `composer.phar`, di atas batas minimum **2.8.11**
(rilis pertama dengan fix deprecation 8.5). Catatan lama "2.6.6" sudah tidak berlaku. Karena itu
**`self-update` tidak dijalankan**; langkah ini hanya verifikasi + backup PHAR.

```powershell
# 3b. Konfirmasi executable yang aktif dan simpan versi awal
Get-Command composer -All
composer --version

# Simpan backup PHAR aktif ke checkpoint runtime (wajib, terlepas dari perlu-tidaknya update)
Copy-Item 'C:\ProgramData\ComposerSetup\bin\composer.phar' 'D:\laragon\backup\website-kolam-pre-runtime-upgrade\composer.phar'
certutil -hashfile 'D:\laragon\backup\website-kolam-pre-runtime-upgrade\composer.phar' SHA256

# HANYA jika `composer --version` < 2.8.11:
# composer self-update --stable
# if ($LASTEXITCODE -ne 0) { throw 'Update Composer gagal. Periksa output sebelum lanjut.' }
composer --version
```

**Ekspektasi:** `Composer version 2.11.2 ...` dan SHA256 PHAR backup tercatat, **tanpa** self-update.
(`Get-FileHash` tidak tersedia di PowerShell 5.1 mesin ini — sudah diverifikasi 13 Nov 2026 —
karena itu dipakai `certutil -hashfile`.)

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
| 0a | Audit dependency bersih | `composer audit` | **0 advisory** (dari 13) | **LULUS** — `composer audit` exit 0, `No security vulnerability advisories found.` (dari 13 advisory / 7 paket). Bukti `audit-b3.txt`, `batch-g/g3-audit.txt` |
| 0b | Platform check bersih | `composer check-platform-reqs` | semua `success` | **LULUS** — `check-platform-reqs` exit 0, semua `success` (detail di #7) |
| 0c | Test setelah Fase 0 | `php artisan test --compact` | **32 passed** | **LULUS** — 32 passed / 130 assertions, exit 0 (B3) |
| 1 | PHP CLI versi baru | `php -v` (terminal baru) | versi patched target; 8.5.12 atau patch lebih baru yang diverifikasi | **LULUS (dengan catatan)** — `php-8.5.12\php.exe -v` → `PHP 8.5.12 (cli) (ZTS Visual C++ 2022 x64)`. Catatan: `php` di PATH terminal masih 8.3.30 (risiko PATH); semua perintah memakai path eksplisit |
| 2 | Resolusi executable benar | `where.exe php`, `Get-Command php -All` | executable aktif dari folder target | **BELUM** — `where php` → `php-8.3.30-Win32-vs16-x64` (urutan PATH mesin belum diubah). Tidak memblokir SAPI web; risiko #2 masih terbuka |
| 3 | Extension lengkap | `php -m` | 14 extension §3 | **LULUS** — 14/14 extension target termuat (E1) |
| 4 | SAPI web versi baru | probe JSON Langkah 9 lalu dihapus | versi target, apache2handler, ini baru, hardening false, extension lengkap | **LULUS** — probe: `php_version 8.5.12`, `sapi apache2handler`, INI baru, `expose_php ""`, `upload_progress false`, `fatal_error_backtraces "0"`, `extension_dir` 8.5.12; tanpa header `X-Powered-By` (`e2-webprobe.json`) |
| 5 | File cek dihapus | `Test-Path public\_check.php` | `False` | **LULUS** — `public/_check.php` tidak ada (diverifikasi ulang di Batch G); `GET /_check.php` → 404 |
| 6 | Wiring Apache | `Select-String mod_php.conf 'php-8.3.30'` | tidak ada hasil | **LULUS** — 4 baris `mod_php.conf` menunjuk `php-8.5.12-Win32-vs17-x64`, blok mime utuh (D3) |
| 7 | Platform check (post-upgrade) | `composer check-platform-reqs` | semua success | **LULUS** — exit 0, 21 baris `success` termasuk `php 8.5.12`, 0 `failed` (`batch-g/g2-platform.txt`) |
| 8 | Bootstrap app | `php artisan about` | tanpa error, PHP 8.5.12 | **LULUS** — exit 0, `Laravel Version 13.35.0`, `PHP Version 8.5.12`, `Composer Version 2.11.2` (`batch-g/g4-about.txt`) |
| 9 | Test suite | `php artisan test --compact` | **32 passed** | **LULUS** — 32 passed / 130 assertions, exit 0, diulang setelah `composer update --lock` (`batch-g/g3-tests.txt`) |
| 10 | Deprecation app | log + `--display-deprecations` | tidak ada dari `app/`, `database/`, `routes/`, `tests/` | **LULUS** — 0 `Deprecated` di output test; `storage/logs/laravel.log` (818.018 B) tanpa entri 8.5/deprecation; `tmp/php_errors.log` tanpa warning baru |
| 10b | Hardening aktif | `php -i` grep 3 directive | `Off` / `Off` / `Off` | **LULUS** — `expose_php Off`, `fatal_error_backtraces Off`, `session.upload_progress.enabled Off` |
| 10c | Composer modern | `composer --version` | ≥ 2.8.11 (aktual 2.11.2 — sudah terpenuhi) | **LULUS** — Composer 2.11.2 berjalan di PHP 8.5.12, PHAR dibackup (`dd158c0a…`) |
| 11 | Upload gambar | `/admin` → Proyek → upload | tersimpan & tampil | **BELUM (opsional, butuh browser)** — `FileUpload` butuh multipart + endpoint `livewire/upload-file` sungguhan. Bukan blocker PHP 8.5; dicatat sebagai verifikasi manual opsional |
| 12 | Panel admin | login + 6 resource terbuka | tanpa error | **LULUS** — login Filament berhasil (`effects.redirect` → `/admin`), 7 halaman panel + 5 form create + 5 form edit semuanya 200, 0 error (E5) |
| 13 | Landing page | manual (Langkah 12) | semua checklist lulus | **LULUS** — `GET /` 200 (116.250 B), CSS/JS 200, 0 error di HTML; `/storage/media/…` 200 |
| 14 | Versi dan keputusan metadata tercatat | dokumen yang disetujui operator / log eksekusi | versi aktual dan apakah minimum PHP diubah; bila Langkah 14 ditolak, alasan dicatat | **LULUS** — operator **menyetujui** minimum PHP `>=8.5.12 <8.6`; `TODO.md` (bagian G), `DOKUMENTASI.md` baris 27, `AGENTS.md` + `CLAUDE.md` baris 8 diperbarui. Commit **tidak** dilakukan (pilihan operator). Lihat Lampiran C |
| 15 | Checkpoint tersedia / rollback diuji bila dipilih | metadata per fase, konfigurasi, DB/upload | jika rollback diuji, kembali lagi ke runtime patched dan ulangi validasi | **LULUS** — checkpoint `D:\laragon\backup\website-kolam-pre-runtime-upgrade` (metadata, `vendor`, SQL teruji restore, upload ber-hash) + `batch-g/composer.json.before` dan `composer.lock.before`. Rollback tidak diuji (opsional) |
| 16 | Empat proyek lain tervalidasi | platform check, test tersedia, smoke test per proyek | hasil dan keterbatasan dicatat; tidak ada regresi blocker | **Sebagian** — platform 19/19 success di keempatnya; F4 regresi 8.5 diperbaiki (2 passed); blocker non-runtime: WIP `klinik-kasir`, `public/build` `sistem-pembayaran-klinik`. Lampiran C |
| 17 | Akses jaringan ditinjau | binding, firewall, tunnel, port forwarding | exposure aktual dicatat, bukan diasumsikan nol | **LULUS untuk bagian lokal** — listener `0.0.0.0:80/443` (httpd 16664) dan `127.0.0.1:3306` (mysqld 22052); `33060` mati; rule firewall `Apache HTTP Server` hanya `Profiles: Private` (2 rule, Enabled). Port forwarding/tunnel di router **tidak dapat diverifikasi dari mesin ini** — butuh konfirmasi operator (lihat risiko terbuka) |

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
| `composer` memuntahkan banyak `Deprecated:` | Composer < 2.8.11 (di mesin ini 2.11.2, jadi bukan penyebab) | Langkah 3b |
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
| 2026-11-13 23:05–23:11 +08:00 | **A1** — Perlindungan pekerjaan operator | Zed agent | Selesai | Git: hanya `plan_migrasi.md` modified; `.graphifyignore`, `PLAN-FAQ.md`, `graphify-out/` untracked. Semuanya dipertahankan; snapshot dokumen dan arsip untracked dibuat. Tidak commit/stash/reset. |
| 2026-11-13 | **A2** — Inventaris aktual | Zed agent | Selesai | CLI PHP 8.3.30 ZTS VS16 x64; INI dan extension_dir runtime lama. Composer 2.11.2 via `C:\ProgramData\ComposerSetup\bin\composer.bat` → `composer.phar` → PHP dari PATH. Laravel 13.35.0, Filament 5.10.1, Livewire 4.4.7, PHPUnit 12.5.38. Detail: `inventory-before.txt` di checkpoint. |
| 2026-11-13 | **A3** — Exposure | Zed agent + operator | Belum lengkap | Apache/Laragon tidak berjalan; tidak ada listener 80/443/8000/8080. Tidak ditemukan proses tunnel umum atau aturan Windows portproxy. Konfigurasi `Listen 80`, SSL `Listen 443`; vhost `*:80`, `Require all granted`. Dua rule Apache Allow Public (TCP/UDP, Any port/address); Wi-Fi aktif Public. Operator memilih forwarding eksternal belum diketahui. Tidak mengubah firewall/binding. |
| 2026-11-13 | **A4** — Target & prasyarat | Zed agent | Selesai | Sumber resmi mengonfirmasi PHP 8.5.12 security release dan build 8.5.12-1 VS17 x64 TS. VC++ x64 Installed=1, v14.51.36247.00. Disk D: bebas 169221275648 byte (~157.6 GiB). mysqldump 8.4.3 dan 7-Zip 23.01 tersedia; ACL memberi Authenticated Users Modify pada PHP/apache2/tmp/backup. Browser Chrome terdeteksi. Hash publikasi dicatat di bawah; ZIP belum diunduh. |
| 2026-11-13 | **A5 / F0-a** — Checkpoint pra-update | Zed agent | Selesai | `D:\laragon\backup\website-kolam-pre-security-update-20261113-2305`; manifest/lock identik lewat `cmp`, `vendor-before.7z` (17183 file) dan `operator-untracked-before.7z` (129 file) lolos `7z t` exit 0. SHA256 disimpan; checkpoint baru, tidak menimpa backup lama. |
| 2026-11-13 | **A6 / F0-a** — Baseline | Zed agent | Selesai | Audit exit 1: 13 advisory / 7 paket, feed berhasil, stderr kosong. Platform exit 1: hanya `symfony/yaml 8.0.15` membutuhkan PHP >=8.4. PHPUnit langsung exit 0: 32 test / 130 assertion, 19.826 detik. Dua percobaan wrapper Artisan exit 2 sebelum test berjalan; detail di bawah. |
| 2026-11-13 | **Revisi to-do** — A3→D0, Batch A, cakupan B1, Composer | Zed agent + operator | Selesai | A3 dikembalikan ke `[ ]` + `Ditunda` dan menjadi **D0** (blocker `Start All`, termasuk MySQL 3306/root tanpa password dan 4 rule firewall Public); Batch A = `Selesai`; cakupan B1 dicatat 21 paket; Composer 2.11.2 menggantikan catatan 2.6.6. Dasar: dry-run 0/21/0, audit 13/7 tereproduksi, SHA256 checkpoint cocok, test 32/130, nol listener aktif. |
| 2026-11-13 23:36 | **B1 / F0-b** — Dry-run terarah | operator + agent | Lulus | Constraint §5.4 dijalankan ulang: `0 installs, 21 updates, 0 removals` exit 0; framework tetap; `symfony/yaml v8.0.15 → v7.4.23`; 21 paket terdaftar dengan patch/minor; audit `audit-b1.json` = 13 advisory / 7 paket, identik dengan baseline (13/13 ID sama); hash `composer.json 076efd35…` dan `composer.lock b2e9c150…` tidak berubah; `git status` tetap `M plan_migrasi.md` + 3 untracked lama. Bukti: `b1-dryrun.txt`, `audit-b1.json`. |
| | **F0-a** — Checkpoint pra-update + baseline | | | Path checkpoint; versi CLI/Composer; audit aktual; test aktual |
| | **F0-b** — Dry-run update + persetujuan cakupan | | | Daftar perubahan aktual; bandingkan catatan lama 21 update |
| 2026-11-13 | **B2 / F0-c** — Update dependency | operator + agent | Lulus | Dry-run gate diulang: `0/21/0` identik B1. `composer update` exit 0 → `0 installs, 21 updates, 0 removals`; 21 paket = cakupan disetujui; `symfony/yaml v8.0.15 → v7.4.23`; `laravel/framework v13.35.0` tetap; `content-hash` tidak berubah. Script: `package:discover` DONE (25 paket); `vendor:publish --tag=laravel-assets` → `No publishable resources`, `public/` utuh (43/43 hash sama). Bukti: `b2-dryrun-confirm.txt`, `b2-update.txt`. |
| 2026-11-13 | **B3 / F0-d** — Verifikasi Fase 0 | operator + agent | Lulus | `composer audit` exit 0 → **0 advisory** (dari 13/7). `composer check-platform-reqs` exit 0 → semua `success`, termasuk `php 8.3.30` (sebelumnya gagal di `symfony/yaml`). `php artisan test --compact` exit 0 → **32 passed / 130 assertions** (sama dengan baseline). Bukti: `audit-b3.txt`, `audit-b3.json`, `platform-b3.txt`, `tests-b3.txt`. |
| 2026-11-13 | **B4 / F0-d** — Review diff Fase 0 | operator + agent | Lulus | `git status --short`: hanya `M composer.lock` + `M plan_migrasi.md` + 3 untracked lama; `public/` dan `bootstrap/cache/` bersih. Diff lock 187+/150−; perbandingan programatik dengan checkpoint = **tepat 21 perubahan versi**, 0 install, 0 removal, `laravel/framework` utuh. `composer.lock` baru `355824e9…`; `composer.json` tetap `076efd35…`. Tidak commit/stash/reset. |
| 2026-11-13 23:56–23:57 | **C2 / 1** — Backup DB + upload | Zed agent + operator | Lulus | `mysqldump` exit 0 → `website_kolam.sql` 151.958 byte, 16 tabel, utf8mb4 valid, SHA256 `dca1f422…d91a`. **Uji restore lolos:** `website_kolam_restore_check` dibuat, restore exit 0, 16/16 tabel + jumlah baris per tabel **identik** sumber, lalu DB uji di-DROP (exit 0). Upload: 26 file / 29.869.443 byte, SHA256 seluruh file identik. MySQL dinyalakan **hanya** loopback (`--bind-address=127.0.0.1 --mysqlx=OFF`, listener `127.0.0.1:3306` saja) sesuai pilihan operator, lalu dimatikan; D0 tetap belum ditutup. |
| 2026-11-13 23:52 | **C3 / 2** — Unduh + checksum | Zed agent | Lulus | `sha256sum.txt` resmi diambil **ulang** saat eksekusi (10.199 byte). `php-8.5.12-1-Win32-vs17-x64.zip` 36.453.069 byte; SHA256 lokal `0dacd6484b092b5e9ae9fa3d7f96a937caf9d874eee2d980b68ed3129741144a` **= baris resmi**. Diverifikasi dua cara: `certutil -hashfile` dan `sha256sum`. |
| 2026-11-13 23:57 | **C4 / 3** — Ekstrak | Zed agent | Lulus | Tidak ada `httpd.exe`/`mysqld.exe` berjalan; target belum ada. 40 entri di `D:\laragon\bin\php\php-8.5.12-Win32-vs17-x64` tanpa folder bersarang; `php.exe`, `php8apache2_4.dll`, `php8ts.dll`, `php.ini-development`, `ext\` (36 DLL) semua ada. `php.exe -v` → 8.5.12 ZTS VS17 x64, `Thread Safety => enabled`. Runtime 8.3.30 tetap utuh dan masih jadi PHP aktif. |
| 2026-11-13 23:58 | **C5 / 3b** — Verifikasi Composer | Zed agent | Lulus | Wrapper aktif `C:\ProgramData\ComposerSetup\bin\composer.bat`, `Composer 2.11.2` (≥ 2.8.11) → **tanpa** `self-update`. PHAR dibackup ke checkpoint, SHA256 `dd158c0a…d028`, versi dari salinan tetap 2.11.2. Salinan Laragon 2.9.4 (tidak aktif, ≥ minimum) dibiarkan. Uji ke depan: PHAR 2.11.2 + `php-8.5.12` → exit 0, `PHP 8.5.12`, **nol** baris `Deprecated:`. |
| | 4 — `php.ini` baru | | | Sisa path lama: |
| | 5 — Hardening ini | | | |
| | 6 — Switch + audit `mod_php.conf` | | | |
| | 7 — Restart | | | |
| 2026-11-14 00:11 | **D0 / A3** — Exposure | Zed agent + operator | **Sebagian** | `netsh advfirewall firewall set rule … new profile=private` **gagal**: `requires elevation (Run as administrator)`; sesi non-elevated (`IsInRole(Administrator)`=`False`), elevasi tidak diminta. Mitigasi dijalankan di MySQL: `my.ini` + `bind-address=127.0.0.1`, `mysqlx=OFF` (backup `my.ini.bak-d0`). Terbukti: listener hanya `127.0.0.1:3306`, `@@bind_address`=`127.0.0.1`, koneksi `10.202.57.142:3306` → `ERROR 2003 (10061)`, loopback OK, lalu `Shutdown complete`. **Sisa:** 2 rule Apache masih `Profiles: Public` → `Start All` belum boleh. |
| 2026-11-14 00:15 | **D1 / 4** — `php.ini` baru | Zed agent | Lulus | Disalin dari php.ini 8.3.30; **hanya 1 baris** berubah (`extension_dir` baris 667). Grep `php-8.3.30` kosong. UTF-8 tanpa BOM, CRLF dipertahankan (1669 CRLF / 0 LF tunggal). Baris non-versi (error_log 515, include_path 647, sendmail_path 962, session.save_path 1180, curl.cainfo 1638, memory_limit, upload/post max) tidak berubah. Daftar `^extension=` identik 8.3 vs 8.5; 14/14 extension aktif + DLL ada; `php -m` memuat semuanya. |
| 2026-11-14 00:18 | **D2 / 5** — Hardening | Zed agent | Lulus | `expose_php=Off` (343), `session.upload_progress.enabled = 0`, `fatal_error_backtraces = 0`, `report_memleaks=On` dihapus (472). **Temuan tambahan:** `session.sid_length=26` & `session.sid_bits_per_character=5` memicu `Deprecated: PHP Startup: … is deprecated` di 8.5 → keduanya dihapus sehingga startup bersih. Verifikasi `php -i`: `expose_php => Off`, `fatal_error_backtraces => Off`, `session.upload_progress.enabled => Off`, `memory_limit => 512M`, `sid_length => 32`, `sid_bits_per_character => 4`. `;zend_extension=opcache` tetap dikomentari. Bukti: `d1-phpini-ini.txt`, `d1-phpini-sha.txt`, `php.ini.8.5.12-created`. |
| 2026-11-14 00:19 | **D3 / 6** — Switch runtime | operator | Lulus | Operator memilih PHP 8.5.12 di UI Laragon. `mod_php.conf` keempat baris → `php-8.5.12-Win32-vs17-x64`; blok `<IfModule mime_module>` + `AddType` utuh; nol sisa path 8.3.30. |
| 2026-11-14 00:18 | **D4 / 7** — Restart | operator | Lulus | `httpd` PID 16664+17528, `mysqld` PID 22052. Listener 80/443 di `0.0.0.0`, 3306 di `127.0.0.1` saja (33060 mati). `error_log`: `Apache/2.4.66 (Win64) OpenSSL/3.5.9 configured`, `Starting 64 worker threads` — 0 warning/0 error sejak start. |
| 2026-11-14 00:35 | **D0 / A3** — Exposure | operator | **Lulus** | Operator mencabut 2 rule Public. Terverifikasi: `show rule name="Apache HTTP Server"` → **2 rule, keduanya `Profiles: Private`** (UDP+TCP); rule Public hilang. Profil jaringan Public → 80/443 tidak terjangkau dari LAN. MySQL tetap loopback-only + `mysqlx=OFF`. Catatan: `curl` ke 10.202.57.142/192.168.56.1 tetap 200 karena keduanya interface mesin ini (trafik lokal), bukan bukti terbuka. |
| 2026-11-14 00:22 | **E1 / 8** — CLI | Zed agent | Lulus | `PHP 8.5.12 (cli) (ZTS Visual C++ 2022 x64)`; INI = `php-8.5.12…\php.ini`; 14/14 extension; `expose_php/fatal_error_backtraces/upload_progress` = Off; Composer 2.11.2 di atas 8.5.12. Catatan: PATH mesin masih memuat entri 8.3.30 lebih dulu. |
| 2026-11-14 00:22 | **E2 / 9** — SAPI web | Zed agent | Lulus | Probe `/_check.php` → `php_version 8.5.12`, `sapi apache2handler`, INI & extension_dir baru, 8 extension `true`, hardening Off, header tanpa `X-Powered-By`. Bukti `e2-webprobe.json`; probe dihapus → `/_check.php` **404**. |
| 2026-11-14 00:23 | **E3 / 10** — Dependency & aplikasi | Zed agent | Lulus | `check-platform-reqs` exit 0 semua success (`php 8.5.12 success`, 0 failed); `composer audit` exit 0 **0 advisory**; Laravel 13.35.0; 37 route; `migrate:status` 13 `Ran`, 0 pending; tidak ada `config.php`. Bukti: `e3-platform.txt`, `e3-audit.txt`. |
| 2026-11-14 00:24 | **E4 / 11** — Test & log | Zed agent | Lulus | PHPUnit di PHP 8.5.12 exit 0: **32 passed / 130 assertions** (sama baseline), 0 deprecation/warning/notice. `laravel.log` tanpa entri baru (terakhir 2026-10-16). `php_errors.log` terakhir `16:13:47 UTC` (pra-`Start All`) → tidak ada warning baru. Bukti: `e4-tests.txt`. |
| 2026-11-14 00:36 | **E5 / 12** — Manual (panel admin) | Zed agent | **Lulus** | Kredensial seeder diverifikasi (`Hash::check` → YA). Login Filament via Livewire `POST /livewire-577a3a78/update` call `authenticate` → 200, `effects.redirect` = `/admin`, errors kosong. Read-only: Dasbor 67 KB; faqs 6 baris, projects 5, services 3, packages 3, videos 5, site-settings terisi lengkap; 5 form create + 5 form edit (101–129 ms) semua 200 `err=0`. Sisa: FileUpload + console browser (butuh browser). |
| 2026-11-14 00:25 | **E6 / 12** — Pembersihan | Zed agent | Lulus | Tidak ada record/file uji (E5 baca-saja). Hanya 1 baris sesi login di tabel `sessions`. Probe `_check.php` dihapus & 404. Skrip uji di `%TEMP%` dihapus. `git status` bersih dari file uji. Dua entri `php_errors.log` 16:32 = bug skrip uji saya, bukan aplikasi. |
| | 13 — Rollback (jika dipakai) | | | Fase/checkpoint dipulihkan; hasil CLI/web/test; versi patched akhir |
| | 14 — Repo | | | Commit: |
| | 15 — Pencegahan | | | |
| | Proyek bersama — klinik-kasir | | | Platform/test/smoke test; keterbatasan |
| | Proyek bersama — sistem-pembayaran-klinik | | | Platform/test/smoke test; keterbatasan |
| | Proyek bersama — web-igrgto | | | Platform/test/smoke test; keterbatasan |
| | Proyek bersama — yayasan-bhakti-abu-rohana | | | Platform/test/smoke test; keterbatasan |
| | Exposure jaringan | | | Binding/firewall/tunnel/port forwarding aktual |

### Bukti Batch A — 13 November 2026

**Checkpoint pra-dependency:** `D:\laragon\backup\website-kolam-pre-security-update-20261113-2305`.
Berisi `composer.json`, `composer.lock`, snapshot awal `plan_migrasi.md`, arsip vendor dan
untracked, `SHA256SUMS.txt`, `inventory-before.txt`, `audit-before.json`,
`audit-before.stderr.txt`, `platform-before.txt`, serta log percobaan test.
Checkpoint disimpan di luar repo/webroot; jangan ditimpa pada percobaan berikutnya.
Ini bukan backup DB/upload pra-runtime (C2 belum dikerjakan), bukan uji restore penuh,
dan bukan media immutable/WORM.

| Berkas | SHA256 pra-update |
|---|---|
| `composer.json` | `076efd3527e54c924f23599b004e176a84628184842935662a383374ef1b070a` |
| `composer.lock` | `b2e9c15084fe89442fceb7b7d576f10e6267fe9c169c3a40cb92c45ccd74ef3f` |
| `vendor-before.7z` | `b1a012597b84c5caadcc10014bc719e8d15a7ccaddc8ad960a41c980c7bbca82` |
| `operator-untracked-before.7z` | `6ac46b4fb1256359931295a43f8d17f5178eec7af0184bcf32ba36ca503aa33c` |

**Konfigurasi bersama:** `mod_php.conf` menunjuk DLL OpenSSL, `php8apache2_4.dll`, dan
`PHPIniDir` runtime 8.3.30. Apache `httpd-2.4.66-260223-Win64-VS18`: `httpd -t` →
`Syntax OK` (exit 0), `httpd -M` → `mpm_winnt_module` dan `php_module` (exit 0).
Ini pemeriksaan konfigurasi, **bukan verifikasi versi melalui SAPI web**; service tetap mati.
CLI memuat extension yang dibutuhkan (termasuk curl, intl, mbstring, openssl, gd,
fileinfo, pdo_mysql, pdo_sqlite, zip); memory_limit 512M, upload/post max 2G.

Keempat folder proyek bersama dan lock tersedia, vhost mengarah ke `public` masing-masing:
`klinik-kasir` (PHP ^8.3, Laravel ^13.0), `sistem-pembayaran-klinik` (PHP ^8.3,
Laravel ^13.8), `web-igrgto` (PHP ^8.3, Laravel ^13.0), dan
`yayasan-bhakti-abu-rohana` (Laravel ^12.0; tidak ada requirement PHP langsung pada
objek `require`). Ini inventaris manifest, bukan bukti kompatibilitas seluruh lock;
platform/test/smoke test proyek lain tetap menunggu Batch F.

**Sumber target:** [PHP 8.5.12](https://www.php.net/releases/8_5_12.php),
[Windows PHP downloads](https://windows.php.net/download/),
[advisory PHP CVE-2026-109618](https://github.com/php/php-src/security/advisories/GHSA-w8hw-w9fx-j79w),
[advisory PHP CVE-2026-109619](https://github.com/php/php-src/security/advisories/GHSA-6wmf-4rv5-p3rp).
Keduanya mencantumkan patched 8.2.35/8.3.36/8.4.27/8.5.12; target rencana tetap 8.5.12.
SHA256 **publikasi** ZIP VS17 x64 TS build 8.5.12-1:
`0dacd6484b092b5e9ae9fa3d7f96a937caf9d874eee2d980b68ed3129741144a`.
Belum ada ZIP lokal, jadi pencocokan hash unduhan C3 belum dilakukan.
Bukti exploit prefork tidak otomatis membuktikan exploit Windows MPM WinNT/ZTS;
perbedaan ini bukan alasan melewatkan patch.

Target dependency didukung [Livewire 4.4.8 advisory](https://github.com/livewire/livewire/security/advisories/GHSA-mhf7-xp6r-6qrc),
[Symfony 7.4.22](https://symfony.com/blog/symfony-7-4-22-released) (Mime), dan
[Symfony 7.4.23](https://symfony.com/blog/symfony-7-4-23-released) (komponen security lain).
Audit aktual memuat Livewire, HtmlSanitizer, HttpFoundation, HttpKernel, Mime, Routing,
dan Yaml. Validitas resolver/cakupan update belum diuji: itu B1, bukan hasil Batch A.

**Catatan insiden / penyimpangan dari runbook:**

- PowerShell `Get-Acl` gagal memuat modul `Microsoft.PowerShell.Security`; pemeriksaan ACL
  dilanjutkan dengan `icacls` (berhasil). Satu pencarian manifest PowerShell gagal karena
  escaping regex; inventaris `require` kemudian berhasil dibaca sebagai JSON.
- `php artisan test --compact --no-interaction` → exit 2, PHPUnit menolak opsi
  `--no-interaction` (`tests-before.txt`). Percobaan `php artisan --no-interaction test --compact`
  → exit 2, `Test file "test" not found` (`tests-before-retry.txt`). Keduanya kegagalan
  pemanggilan wrapper, bukan assertion aplikasi; tidak mengubah wrapper/dependency untuk preflight.
- Baseline berhasil memakai `php vendor/bin/phpunit --no-progress` dengan konfigurasi
  `phpunit.xml` dan environment proses eksplisit `APP_ENV=testing`, `DB_CONNECTION=sqlite`,
  `DB_DATABASE=:memory:`, `DB_URL=`. `bootstrap/cache/config.php` tidak ada; test upload
  memakai `Storage::fake('public')`. Hasil: **OK (32 tests, 130 assertions)**, exit 0,
  disimpan di `phpunit-before.txt`. Test dapat menghasilkan cache/log/file temporary testing;
  tidak menjalankan migrasi pada DB aplikasi atau mengedit `.env`.
- Tidak ada dependency/runtime/configuration/service yang diubah, tidak ada unduhan runtime,
  dan tidak ada commit/push. A3 belum lengkap: operator belum tahu status forwarding router;
  tidak adanya proses tunnel umum bukan bukti seluruh forwarding/tunnel tidak ada.
  Pertahankan Apache mati sampai review pembatasan akses selesai; Bagian B belum dimulai.

### Bukti Batch B — 13 November 2026

Checkpoint **B1** dipakai ulang; tidak ada checkpoint baru dan tidak ada backup yang ditimpa.
Semua bukti di bawah berada di `D:\laragon\backup\website-kolam-pre-security-update-20261113-2305`.

| Berkas bukti | Isi |
|---|---|
| `b2-dryrun-confirm.txt` | Dry-run gate sebelum update nyata — `0/21/0`, identik B1 |
| `b2-update.txt` | Log `composer update` (exit 0) + output `package:discover` dan `vendor:publish` |
| `b2-gitstatus-before.txt` | `git status --short` sebelum update |
| `b2-public-sha-before.txt` | SHA256 43 file tracked di `public/` sebelum update (pembanding B4) |
| `b2-bootstrapcache-sha-before.txt` | SHA256 `bootstrap/cache/{packages,services}.php` sebelum update |
| `audit-b3.txt`, `audit-b3.json` | Audit pasca-update — 0 advisory, exit 0 |
| `platform-b3.txt` | `check-platform-reqs` — semua `success`, exit 0 |
| `tests-b3.txt` | PHPUnit — 32 passed / 130 assertions, exit 0 |

| Berkas | SHA256 |
|---|---|
| `composer.json` (tidak berubah) | `076efd3527e54c924f23599b004e176a84628184842935662a383374ef1b070a` |
| `composer.lock` pasca-Fase 0 | `355824e9b493a508595c3a984d26b795caa47641984a82aaf9fb83394380490c` |
| `composer.lock` pra-Fase 0 (checkpoint) | `b2e9c15084fe89442fceb7b7d576f10e6267fe9c169c3a40cb92c45ccd74ef3f` |

**Catatan:** `composer update` menulis ke `vendor/` sehingga vendor sekarang **tidak** lagi
identik dengan `vendor-before.7z`. Rollback Fase 0 (§8.1) memakai `composer.lock` dari
checkpoint + arsip vendor tersebut. Runtime, `php.ini`, Apache, MySQL, `.env`, dan `public/`
tidak disentuh pada batch ini; `_check.php` tidak dibuat dan service tetap mati.

**Verifikasi akhir yang menandatangani dokumen ini:**

```text
composer audit                          -> exit 0, No security vulnerability advisories found (pasca-Fase 0)
composer check-platform-reqs            -> exit 0, semua success termasuk php 8.3.30
php -v                                  -> PHP 8.3.30 (cli) ZTS Visual C++ 2019 x64
where.exe php / Get-Command php -All    -> D:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe
php -i + Select-String (3 directive)    -> memory_limit 512M / post_max_size 2G / upload_max_filesize 2G
http://website-kolam.test/_check.php     -> tidak dijalankan (Apache mati; langkah 9 belum masuk)
php artisan test --compact              -> exit 0, 32 passed, 130 assertions
composer --version                      -> Composer 2.11.2 (PHP 8.3.30)
```

### Bukti Batch C — 13 November 2026

Checkpoint **baru**: `D:\laragon\backup\website-kolam-pre-runtime-upgrade`.
Checkpoint pra-dependency `…-pre-security-update-20261113-2305` **tidak** disentuh, dan tidak ada
backup lama yang ditimpa. Lokasi checkpoint berada di luar repo/webroot.

| Berkas di checkpoint runtime | Isi / SHA256 |
|---|---|
| `composer.json` | manifest pasca-Fase 0 — `076efd3527e54c924f23599b004e176a84628184842935662a383374ef1b070a` |
| `composer.lock` | lock pasca-Fase 0 (**sama** dengan repo) — `355824e9b493a508595c3a984d26b795caa47641984a82aaf9fb83394380490c` |
| `composer.phar` | PHAR aktif 2.11.2 — `dd158c0afd41d15fac89e1b4e47b5d42911fe316c3687cd5edac98319b25d028` |
| `website_kolam.sql` | dump utf8mb4 16 tabel, 151.958 byte — `dca1f422253fa3c8b609601aa426fedcf20dbed685069341456655c59c29d91a` |
| `public-uploads/` | 26 file, 29.869.443 byte — hash per file identik dengan `storage/app/public` |
| `c2-tables-before.txt`, `c2-rowcounts-before.txt`, `c2-rowcounts-restored.txt` | inventaris 16 tabel + jumlah baris sumber vs hasil restore (pembanding `diff` bersih) |
| `mysqld-temp.log` | log mysqld loopback sementara (start → `ready for connections` → `Shutdown complete`) |

**Anchor konfigurasi (di lokasi aslinya, nama versi eksplisit):**

| Berkas | SHA256 |
|---|---|
| `D:\laragon\etc\apache2\mod_php.conf` dan `.bak-8.3.30` | `e6462ef9b42e96ebda4a7df2fea1fac8f968e719fc316dc14bf76d9d0d50b073` (identik) |
| `D:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.ini` dan `.bak-8.3.30` | `d9d973f114335cf8c55ca113cded2f89d02ba00872060e8c7e6269b82a3e30a1` (identik) |

**Build terpasang (belum aktif):** `D:\laragon\bin\php\php-8.5.12-Win32-vs17-x64`, 99 MB, 40 entri
di root, `ext\` 36 DLL. Varian terkonfirmasi **TS (Thread Safe) + VS17 + x64** — prasyarat `mod_php`.

**Catatan insiden / penyimpangan dari runbook:**

- **MySQL dinyalakan terbatas untuk C2** (persetujuan operator). `mysqld` dijalankan manual dengan
  `--bind-address=127.0.0.1 --mysqlx=OFF`, sehingga satu-satunya listener TCP adalah `127.0.0.1:3306`
  dan X Plugin 33060 tidak dibuka. Setelah dump + uji restore selesai, server dimatikan via
  `mysqladmin shutdown` (exit 0) dan dikonfirmasi: tidak ada `mysqld.exe`/`httpd.exe` berjalan.
  Ini **bukan** `Start All` Laragon dan tidak menutup **D0**; rule firewall Public dan
  `root` tanpa password masih apa adanya.
- Percobaan pertama `mysqld` tanpa `--mysqlx=OFF` membuka pula `33060` di `0.0.0.0`; proses dihentikan
  dan dijalankan ulang dengan flag tersebut sebelum dump. Log lengkap ada di `mysqld-temp.log`.
- **PowerShell 5.1 `Expand-Archive` gagal:** `Cannot find an overload for "ExtractToDirectory" and the
  argument count: "3"` (kelas masalah yang sama dengan `Get-FileHash`/`Get-Acl` yang sudah dicatat
  di Batch A). Folder target sempat kosong (0 entri) dan **tidak** ada ekstrak separuh jalan.
  Ekstraksi diulang memakai `unzip -q -o` ke folder yang sama; isi arsip diperiksa lebih dulu
  (`unzip -l` → 85 entri, struktur flat tanpa folder akar) sehingga tidak ada risiko nesting.
- Nama folder hasil ekstrak mengikuti nama ZIP (`php-8.5.12-Win32-vs17-x64`), sementara nama berkas
  unduhan memakai sufiks build (`…-8.5.12-1-…`). Ini sengaja: nama folder bersih dipakai `mod_php.conf`
  dan `php.ini` di Langkah 4/6. Bila runbook mau seragam dengan konvensi Laragon, putuskan di D1/D3
  **sebelum** mengedit `PHPIniDir`.
- `Get-ChildItem`/`Where-Object` dari runbook tidak dipakai untuk verifikasi (diganti `ls`/`test -e`),
  karena shell sesi ini bash; hasil yang diperiksa sama.

**Verifikasi akhir yang menandatangani Batch C:**

```text
certutil -hashfile php-8.5.12-1-Win32-vs17-x64.zip SHA256
                                        -> 0dacd648…144a  = sha256sum.txt resmi
D:\laragon\bin\php\php-8.5.12-Win32-vs17-x64\php.exe -v
                                        -> PHP 8.5.12 (cli) (ZTS Visual C++ 2022 x64), Thread Safety enabled
php -v (PATH)                           -> PHP 8.3.30 (cli) ZTS VS16 x64   <-- runtime AKTIF belum berubah
mysqldump … website_kolam               -> exit 0, 151.958 byte, 16 tabel utf8mb4
mysql < website_kolam.sql               -> exit 0 ke website_kolam_restore_check; 16/16 baris identik; DROP ok
sha256sum storage/app/public/* vs public-uploads/*  -> 26/26 identik
composer --version                      -> Composer 2.11.2 (PHP 8.3.30)
php-8.5.12\php.exe composer.phar --version -> Composer 2.11.2 (PHP 8.5.12), exit 0, tanpa Deprecated
tasklist / netstat                       -> tidak ada httpd.exe & mysqld.exe; tidak ada listener 80/443/3306
```

Runtime, `php.ini` aktif, `mod_php.conf`, `.env`, `public/`, dan `bootstrap/cache/` **tidak** diubah
pada batch ini; `_check.php` tidak dibuat. `git status` tetap `M composer.lock` + `M plan_migrasi.md`
+ 3 untracked lama. Tidak ada commit/stash/reset.

### Bukti Batch D (sebagian) — 13–14 November 2026

Batch D **belum selesai**. Yang tercatat di sini adalah D0 (sebagian), D1, dan D2; D3 dan D4
masih menunggu tindakan operator.

**D0 — exposure.** Tindakan firewall tidak dapat dijalankan dari sesi ini karena butuh elevasi:

```text
netsh advfirewall firewall set rule name="Apache HTTP Server" new profile=private
  -> The requested operation requires elevation (Run as administrator).   [exit 1]
netsh advfirewall firewall set rule name="mysqld" new profile=private
  -> The requested operation requires elevation (Run as administrator).   [exit 1]
(Get-CurrentPrincipal).IsInRole(Administrator)  -> False
Get-NetConnectionProfile                        -> "Galaxy A05 9916", NetworkCategory = Public
```

Mitigasi yang dikerjakan dan **diverifikasi** di sisi MySQL:

| Item | Nilai |
|---|---|
| `my.ini` sebelum | SHA256 `4ba9b5055c60369b2de31fb6d4eb48bd763488a2c77526874fda5b7f0c6ddd44` (`d0-firewall-before/my.ini.before`) |
| `my.ini` cadangan lokal | `D:\laragon\bin\mysql\mysql-8.4.3-winx64\my.ini.bak-d0` |
| Perubahan | `bind-address=127.0.0.1`, `mysqlx=OFF` (2 baris komentar penjelas) |
| Listener hasil uji | hanya `TCP 127.0.0.1:3306` — **tidak ada** `0.0.0.0:3306` / `33060` |
| `SELECT @@bind_address` | `127.0.0.1` |
| Koneksi dari `10.202.57.142:3306` | `ERROR 2003 (HY000): Can't connect … (10061)` — ditolak |
| Koneksi loopback | `loopback ok` |
| Setelah uji | `mysqladmin shutdown` exit 0, `Shutdown complete`, nol listener 3306/33060 |

Rule firewall Apache **masih** `Profiles: Public`, `LocalIP: Any`, `RemotePort: Any`,
`Protocol: TCP/UDP`, `Action: Allow` (dump sebelum: `d0-firewall-before/apache-rule-before.txt`,
SHA256 `252dc896…`; mysqld: `mysqld-rule-before.txt`, SHA256 `9dad8a27…`).
Karena itu **`Start All` belum boleh dijalankan**.

**D1 — `php.ini` baru.** `D:\laragon\bin\php\php-8.5.12-Win32-vs17-x64\php.ini`, 73,5 KB,
salinan di checkpoint: `php.ini.8.5.12-created`; SHA256 tercatat di `d1-phpini-sha.txt`.
Perubahan terhadap `php.ini` 8.3.30 hanya:

```ini
extension_dir =  "D:/laragon/bin/php/php-8.5.12-Win32-vs17-x64/ext"   ; baris 667
```

**D2 — hardening.** Tambahan/penghapusan di file yang sama:

```ini
expose_php=Off                                   ; 343, sebelumnya On
session.upload_progress.enabled = 0              ; mitigasi CVE-2026-115560
fatal_error_backtraces = 0                       ; directive baru 8.5
; report_memleaks=On                             ; DIHAPUS (472) — deprecated di 8.5
; session.sid_length=26                          ; DIHAPUS (1285) — deprecated di 8.5
; session.sid_bits_per_character=5               ; DIHAPUS (1319) — deprecated di 8.5
;zend_extension=opcache                          ; tetap dikomentari
```

**Catatan insiden / penyimpangan dari runbook:**

- `netsh advfirewall` gagal karena non-elevated; agent **tidak** mencoba menaikkan hak akses
  sendiri. Mitigasi dipindahkan ke `my.ini` (loopback) dan sisa risiko Apache dicatat sebagai
  tugas operator. Ini berarti D0 **belum** tertutup penuh menurut definisi runbook.
- Uji `mysqld` pertama (pada C2) sempat membuka `33060` di `0.0.0.0` karena `mysqlx` belum
  dimatikan; sekarang `mysqlx=OFF` sudah permanen di `my.ini`.
- Uji awal `php.ini` menghasilkan dua deprecation `session.sid_*` yang tidak disebut runbook.
  Probe sementara (`php.test-probe.ini`) dipakai untuk memastikan penghapusan directive
  membuat startup bersih, lalu probe dihapus.
- `Set-Content`/`WriteAllText` PowerShell sempat menulis `php.ini` dengan **LF saja**; file
  dikembalikan ke **CRLF** (1669 CRLF, 0 LF tunggal) agar konsisten dengan file asli dan
  `php.ini-development`. Perubahan line ending tidak mempengaruhi nilai directive.
- Pemeriksaan extension dengan pola `^extension=$e$` salah menandai `exif` sebagai nonaktif
  karena barisnya berkomentar `; Must be after mbstring`; pola diperlonggar dan hasil akhir
  14/14 aktif.
- `report_memleaks` masih tampil `On` di `php -i` sebagai **nilai default build**, bukan dari
  file; tidak ada lagi baris konfigurasi dan tidak ada deprecation saat startup.

**Verifikasi akhir yang menandatangani Batch D (sebagian):**

```text
php.exe -c php.ini -i                    -> expose_php => Off, fatal_error_backtraces => Off,
                                            session.upload_progress.enabled => Off,
                                            extension_dir => …php-8.5.12…/ext, memory_limit 512M
php.exe -c php.ini -m                    -> 14/14 extension target termuat
php.exe -c php.ini -r 'echo "clean\n";'  -> "clean" tanpa satu pun baris Deprecated
php -v (PATH, runtime aktif)             -> PHP 8.3.30 ZTS VS16 x64  <-- belum di-switch
mod_php.conf                             -> masih 4 baris menunjuk php-8.3.30… (D3 belum)
tasklist                                 -> laragon.exe PID 13956; tidak ada httpd.exe/mysqld.exe
```

D3/D4 belum dijalankan, jadi `_check.php` belum dibuat, `php artisan test` belum diulang di
runtime baru, dan gate D→E belum lulus. `.env`, `public/`, `bootstrap/cache/`, serta
`composer.lock` tidak berubah pada batch ini.

### Bukti Batch D (sisa) + Batch E — 14 November 2026

**Runtime akhir:** PHP **8.5.12** di CLI dan SAPI web (`apache2handler`) — inilah bukti yang
disyaratkan Runbook, bukan `php -v` saja.

```text
mod_php.conf (setelah operator memilih versi di UI Laragon)
  LoadFile     "D:/laragon/bin/php/php-8.5.12-Win32-vs17-x64/libcrypto-3-x64.dll"
  LoadFile     "D:/laragon/bin/php/php-8.5.12-Win32-vs17-x64/libssl-3-x64.dll"
  LoadModule   php_module "…/php8apache2_4.dll"
  PHPIniDir    "D:/laragon/bin/php/php-8.5.12-Win32-vs17-x64"
  <IfModule mime_module> AddType application/x-httpd-php .php </IfModule>   (utuh)

netstat (listener)
  0.0.0.0:80      httpd pid 16664
  0.0.0.0:443     httpd pid 16664
  127.0.0.1:3306  mysqld pid 22052      <-- 33060 tidak ada (mysqlx=OFF)

error_log Apache (Nov 14)
  [mpm_winnt:notice] Apache/2.4.66 (Win64) OpenSSL/3.5.9 configured -- resuming normal operations
  [mpm_winnt:notice] Parent: Created child process 17528
  [mpm_winnt:notice] Child: Starting 64 worker threads
  -> 0 warning / 0 error sejak start
```

**E1 — CLI**

```text
php -v            -> PHP 8.5.12 (cli) (built: Oct 26 2026) (ZTS Visual C++ 2022 x64)
php --ini         -> Loaded Configuration File: …\php-8.5.12-Win32-vs17-x64\php.ini
                     Scan for additional .ini files: (none)
php -i            -> expose_php => Off ; fatal_error_backtraces => Off ;
                     session.upload_progress.enabled => Off
php -m            -> 14/14 extension target termuat
composer --version-> Composer 2.11.2 (PHP 8.5.12)
```

**E2 — SAPI web (probe sementara, kemudian dihapus)**

```json
{
  "php_version": "8.5.12",
  "sapi": "apache2handler",
  "ini": "D:\laragon\bin\php\php-8.5.12-Win32-vs17-x64\php.ini",
  "ext": { "intl": true, "mbstring": true, "gd": true, "fileinfo": true,
           "zip": true, "pdo_mysql": true, "curl": true, "openssl": true },
  "hardening": { "expose_php": "", "upload_progress": false,
                 "fatal_error_backtraces": "0" },
  "extension_dir": "D:\laragon\bin\php\php-8.5.12-Win32-vs17-x64\ext"
}
```

Header respons: `HTTP/1.1 200 OK`, `Server: Apache/2.4.66 (Win64) OpenSSL/3.5.9`,
**tanpa `X-Powered-By`** → `expose_php=Off` benar-benar berlaku di SAPI web.
Setelah probe dihapus: `GET /_check.php` → **404**.

| Berkas bukti (checkpoint `…-pre-runtime-upgrade`) | Isi |
|---|---|
| `e2-webprobe.json` | Output probe SAPI web 8.5.12 |
| `e3-platform.txt` | `check-platform-reqs` exit 0, semua `success` |
| `e3-audit.txt` | `composer audit` exit 0, 0 advisory |
| `e4-tests.txt` | PHPUnit 32 passed / 130 assertions, 0 deprecation |

**E3 — Dependency & aplikasi**

```text
composer check-platform-reqs -> exit 0; php 8.5.12 success; 0 failed
composer audit               -> exit 0; "No security vulnerability advisories found."
php artisan --version        -> Laravel Framework 13.35.0
php artisan route:list       -> Showing [37] routes
php artisan migrate:status   -> 13 migrasi, semua "Ran", tidak ada pending
bootstrap/cache              -> packages.php + services.php; TIDAK ada config.php
```

**E4 — Test & log**

```text
vendor/bin/phpunit --no-progress      -> exit 0, 32 passed, 130 assertions, 4469 ms
grep -ic 'deprecat|warning|notice'    -> 0 (pada e4-tests.txt)
storage/logs/laravel.log              -> entri terakhir 2026-10-16; 0 entri baru; 0 "deprecat"
D:/laragon/tmp/php_errors.log          -> timestamp terakhir 13-Nov-2026 16:13:47 UTC
                                         (sebelum Start All 16:19 UTC) -> tidak ada warning baru
error_log Apache                      -> 0 warning/error sejak start
```

**E5/E6 — Smoke test HTTP (read-only) & pembersihan**

| Permintaan | Status | Catatan |
|---|---|---|
| `GET /` | 200 | 116.250 byte, `<title>Kolam Prima — …</title>`, `app-*.css`/`app-*.js` → 200, 0 error di HTML |
| `GET /admin/login` | 200 | 30.689 byte, form Filament + `wire:submit`; `app.css` dan 3 `app.js` → 200 |
| `GET /storage/media/projects/desain-3d-01.jpeg` | 200 | `image/jpeg`, 116.048 byte → `public/storage` berfungsi |
| `GET /services`, `/projects`, `/faqs`, `/contact` | 404 | **Benar** — aplikasi one-page, `route:list` hanya `/` |
| `GET /_check.php` (setelah dihapus) | 404 | Probe bersih |

**Risiko terbuka (status 14 Nov 2026 00:36):**

1. ~~**D0 — Apache terjangkau dari LAN**~~ → **DITUTUP.** Rule `Apache HTTP Server` sekarang hanya
   `Profiles: Private`; tertulis sebagai bukti di checklist D0 dan Lampiran C.
2. **PATH mesin** masih memuat `php-8.3.30-Win32-vs16-x64` sebelum entri User 8.5.12; terminal di
   luar Laragon bisa menjalankan runtime lama. Belum diperbaiki (butuh hak tulis variabel
   lingkungan mesin); dampaknya hanya ke tooling, bukan ke SAPI web.
3. **Belum teruji (butuh browser)** — unggah file Filament (`FileUpload`) dan console browser
   (Alpine/JS). Tidak menyentuh jalur yang bergantung pada PHP 8.5; dicatat sebagai verifikasi manual
   opsional, bukan blocker.
4. Catatan historis jujur: `netsh … set rule` dijalankan sebelum D3, sehingga port 80/443 sempat
   terekspos ke jaringan Public selama pengujian (runtime yang terekspos **sudah** 8.5.12). Sejak
   00:35 exposure itu ditutup.

### Bukti E5 — Login & jelajah panel admin (read-only) — 14 November 2026

Kredensial `AdminUserSeeder` diverifikasi **di sisi server** sebelum dipakai:

```text
User::where('email','admin@kolamprima.test')->first()  -> ada
hash prefix                                             -> $2y$12$...
Hash::check('password', $user->password)                -> YA
```

Login dikirim sebagai request Livewire asli (bukan sekadar GET halaman):

```text
POST /livewire-577a3a78/update   (Host: website-kolam.test, X-Livewire: true, X-CSRF-TOKEN)
  komponen : filament.admin.auth.login
  updates  : data.email / data.password / data.remember
  call     : authenticate
-> HTTP 200
   components[0].effects.redirect = http://website-kolam.test/admin
   components[0].snapshot.memo.errors = []
```

Jelajah panel setelah login — **GET saja**, tanpa create/update/delete:

| Halaman | HTTP | Ukuran / data | Waktu | Error |
|---|---|---|---|---|
| `/admin` (Dasbor) | 200 | 67.496 B | — | 0 |
| `/admin/faqs` | 200 | **6 baris** | — | 0 |
| `/admin/projects` | 200 | **5 baris** | — | 0 |
| `/admin/services` | 200 | **3 baris** | — | 0 |
| `/admin/packages` | 200 | **3 baris** | — | 0 |
| `/admin/videos` | 200 | **5 baris** | — | 0 |
| `/admin/manage-site-settings` | 200 | form terisi (brand, kontak, sosmed, hero, stat) | — | 0 |
| `/admin/faqs/1/edit` | 200 | 83.261 B | 109 ms | 0 |
| `/admin/projects/10/edit` | 200 | 149.877 B | 129 ms | 0 |
| `/admin/services/1/edit` | 200 | 91.039 B | 103 ms | 0 |
| `/admin/packages/1/edit` | 200 | 118.117 B | 105 ms | 0 |
| `/admin/videos/1/edit` | 200 | 95.902 B | 101 ms | 0 |
| `/admin/{faqs,projects,services,packages,videos}/create` | 200 | form + `wire:model` 3–6 field | — | 0 |
| `/` (publik) | 200 | 116.250 B | 96 ms | 0 |

Yang terbukti berjalan di PHP 8.5.12: autentikasi Filament, sesi `database`, tabel + filter +
paginasi resource, form skema ter-hydrate, widget dasbor, pengaturan situs dengan data nyata,
dan pemrosesan Livewire. **0** deprecation, **0** fatal, **0** stack trace di seluruh HTML.

**Tidak teruji dari klien HTTP (bukan blocker):** unggah `FileUpload` (butuh `multipart` +
endpoint `livewire/upload-file` sungguhan) dan console browser (Alpine/JS interaktif).

### Bukti Batch G — Pencatatan & penutupan — 14 November 2026

**G1 — keputusan operator (dicatat, bukan diasumsikan).** Minimum PHP aplikasi **dinaikkan**:
`composer.json` → `"php": ">=8.5.12 <8.6"` (sebelumnya `^8.3`). Commit/push **tidak** dilakukan
(pilihan operator); perubahan dibiarkan sebagai working tree.

**G2 — metadata & dokumen.**

```text
composer validate          -> "./composer.json is valid"
composer update --lock     -> exit 0; "Nothing to modify in lock file"; package:discover OK; 0 advisory
composer install           -> exit 0; "Nothing to install, update or remove" (vendor sudah sesuai lock)
diff composer.lock         -> hanya "content-hash": 4e04d018…545c8baef -> 0d8d691b…e43455 (+2/-2 baris)
```

Dokumen yang diperbarui: `TODO.md` (bagian **G. Keamanan runtime PHP**, 6 entri + tanggal "Terakhir
diperbarui"), `DOKUMENTASI.md` baris 27 (`| Bahasa | PHP | 8.5.12 |`), `AGENTS.md` dan `CLAUDE.md`
baris 8 (`running on PHP 8.5.12`) — kedua berkas tetap identik (`cmp` lolos).

**G3 — validasi ulang setelah perubahan metadata** (wajib, karena `update --lock` menjalankan script):

```text
composer check-platform-reqs -> exit 0; 21 baris success (php 8.5.12), 0 failed
composer audit               -> exit 0; "No security vulnerability advisories found."
php artisan test --compact   -> exit 0; 32 passed, 130 assertions, 3945 ms
php artisan about --only=env -> Laravel 13.35.0; PHP 8.5.12; Composer 2.11.2; URL website-kolam.test
public/_check.php            -> tidak ada; storage/logs/laravel.log tanpa deprecation
```

**G4 — keadaan akhir.** Runtime patched aktif di CLI (dipanggil eksplisit) dan SAPI web; probe uji bersih;
tidak ada data uji baru; status Git dipahami (lihat di bawah). DoD: #0a, #0b, #0c, #1, #3–#10c, #12–#17
terpenuhi; **#2 belum** (urutan PATH mesin) dan **#11 belum** (upload Filament, butuh browser). Keduanya
dicatat sebagai risiko/keterbatasan, bukan blocker — dan keduanya sudah tercatat sejak Batch E.

**Status Git pada akhir Batch G (tidak di-commit atas permintaan operator):**

```text
 M composer.json        (php >=8.5.12 <8.6)
 M composer.lock        (content-hash saja)
 M plan_migrasi.md      (checklist, Lampiran C, DoD, titik lanjut)
 M TODO.md, DOKUMENTASI.md, AGENTS.md, CLAUDE.md
?? PLAN-FAQ.md, .graphifyignore, graphify-out/   (artefak lama, bukan dari batch ini)
```

| Berkas bukti (`…-pre-runtime-upgrade/batch-g/`) | Isi |
|---|---|
| `composer.json.before`, `composer.lock.before` | Metadata sebelum Langkah 14 (titik pulih) |
| `g2-update-lock.txt`, `g2-install.txt`, `g2-platform.txt` | `update --lock`, `install`, platform check |
| `g3-audit.txt`, `g3-tests.txt` | Audit 0 advisory; 32 passed / 130 assertions |
| `g4-about.txt` | `artisan about` → PHP 8.5.12, Laravel 13.35.0 |

**Kesimpulan Batch G:** langkah pencatatan selesai. Yang **belum** adalah Batch H (pencegahan kambuh:
`composer audit` di CI, Dependabot/Renovate, jadwal cek patch) dan dua item opsional (#2 PATH, #11 upload
browser). Migrasi runtime dinyatakan **selesai untuk sisi runtime + dependensi + dokumen**; Batch H tetap
terbuka sebagai tindak lanjut terpisah.

### Bukti Batch H — Pencegahan lanjutan — 14 November 2026

**Keputusan operator (dicatat, bukan diasumsikan):**

| Item | Keputusan |
|---|---|
| H1 | **Dua tempat**: `scripts.test` **dan** GitHub Actions |
| H2 | **Ditunda** — `.github/dependabot.yml` tidak dibuat |
| H3 | **Belum ditetapkan** — tanpa nama, tanpa tanggal |
| Git | **Tidak commit** — dibiarkan sebagai working tree |

**H1a — `composer audit` di `scripts.test`.** `composer.json` berubah satu blok saja:

```json
"test": [
    "@php artisan config:clear --ansi @no_additional_args",
    "@composer audit --no-interaction",
    "@php artisan test"
],
```

Posisi audit **sebelum** PHPUnit supaya advisory baru menggagalkan `composer test` cepat,
tanpa membuang waktu menjalankan seluruh suite lebih dulu.

```text
composer validate --no-check-publish -> "./composer.json is valid"
composer audit --no-interaction      -> exit 0; "No security vulnerability advisories found."
composer test                        -> config:clear OK; audit "No security vulnerability advisories found.";
                                        32 passed, 130 assertions, 33151 ms; exit 0
```

**H1a — semantik gate diuji, bukan diasumsikan.** Dibuktikan di folder sementara **terpisah**
(`/tmp/auditfail`, sudah dihapus) dengan `scripts.test` yang identik; aplikasi **tidak** disentuh dan
`composer.lock` aplikasi tidak diubah:

```text
Lock tanpa advisory   -> "No packages - skipping audit." / "TEST_STEP_RAN" / exit=0
Lock dengan advisory  -> "Script @composer audit ... handling the test event returned with error code 1"
                         / exit=1 / "TEST_STEP_RAN_SHOULD_NOT_HAPPEN" TIDAK tercetak
```

Artinya `@composer audit` benar-benar **gate keras**: langkah test tidak dijalankan setelah audit gagal.

**H1b — GitHub Actions.** `.github/workflows/tests.yml` (baru, YAML divalidasi `yaml.safe_load`):

| Bagian | Isi |
|---|---|
| Trigger | `push`/`pull_request` ke `main`, `schedule` `0 6 * * 1` (mingguan), `workflow_dispatch` |
| Job `audit` | PHP 8.5 (`shivammathur/setup-php`), `composer install`, `composer audit --no-interaction` |
| Job `phpunit` | PHP 8.5 + ext `sqlite3`/`pdo_sqlite`, Node 22 + `npm ci` + `npm run build` (dibutuhkan `@vite` di Blade), `composer test` |
| Lain-lain | `permissions: contents: read`, `concurrency` cancel-in-progress |

Job dipisah supaya kegagalan audit terlihat sebagai cek tersendiri, tidak terkubur di log PHPUnit.
**Belum terbukti berjalan di GitHub** — workflow baru ada di working tree dan belum di-push; bukti
saat ini terbatas pada validitas YAML + eksekusi perintah yang sama secara lokal.

**H2 — ditunda.** Tidak ada `dependabot.yml`/`renovate.json`. **Risiko terbuka:** tidak ada PR otomatis
untuk advisory baru; mitigasinya job `audit` mingguan di CI (H1). Penundaan dicatat agar tidak menyamarkan
gate.

**H3 — belum ditetapkan.** Tidak ada penanggung jawab/jadwal formal. **Risiko terbuka:** pemeriksaan
patch PHP bergantung pada disiplin manual; pengingat sementara ada di `TODO.md` G5 dan `schedule` CI.
Konsekuensi: **Batch H belum tuntas penuh** — H1 dan H4 selesai, H2 dan H3 terbuka.

**H4 — catatan deployment** ditulis di badan Batch H: pin `8.5.x` terbaru (dukungan keamanan s/d
31 Des 2029), restart queue worker/long-running process setelah setiap upgrade PHP (proses tetap
memakai binary yang ia mulai; padanan lokal = restart Apache setelah ubah `php.ini`), dan WAF bukan
pengganti patch.

**Tidak ada perubahan pada runtime, dependency, atau database** di batch ini: `composer.lock` tidak
berubah (hanya `composer.json` bertambah satu baris script), tidak ada `composer update`, tidak ada
migrasi, tidak ada data uji baru.

| Berkas bukti (`…-pre-runtime-upgrade/batch-h/`) | Isi |
|---|---|
| `composer.json.before` | `composer.json` sebelum H1a (titik pulih) |
| `h1-audit.txt` | `composer audit --no-interaction` exit 0, 0 advisory |
| `h1-test.txt` | `composer test` → audit bersih + 32 passed / 130 assertions, exit 0 |
| `h1-test-final.txt` | Verifikasi ulang setelah dokumen diperbarui: sama, 32 passed / 130 assertions |
| `h1-audit-negative.txt` | Uji semantik gate: audit gagal ⇒ script abort exit 1, test tidak jalan |

**Status Git pada akhir Batch H (tidak di-commit atas permintaan operator):**

```text
 M composer.json            (scripts.test + @composer audit)
 M composer.lock            (sisa Batch G: content-hash saja)
 M plan_migrasi.md          (Batch H, tabel batch, titik lanjut, bukti ini)
?? .github/workflows/tests.yml
?? PLAN-FAQ.md, .graphifyignore, graphify-out/   (artefak lama, bukan dari batch ini)
```

**Kesimpulan Batch H:** pencegahan kambuh **terpasang di sisi teknis** (audit jadi gate lokal + CI,
catatan deployment tertulis). Yang **belum** adalah komitmen organisasi (H2 penjadwalan dependensi,
H3 penanggung jawab) dan verifikasi workflow di GitHub setelah push. Gate migrasi Fase 0/1 tidak
dipengaruhi oleh H2/H3 — keduanya tindak lanjut tata kelola, bukan syarat runtime.

### Bukti Batch F — Validasi proyek bersama — 14 November 2026

**Cara kerja.** Semua perintah dijalankan **eksplisit** dengan biner patched, karena `php` di
PATH terminal masih 8.3.30 (risiko #2): `D:\laragon\bin\php\php-8.5.12-Win32-vs17-x64\php.exe`
(terverifikasi: `php --ini` → `…php-8.5.12-Win32-vs17-x64\php.ini`). Composer = PHAR 2.11.2 yang
sama dengan Batch C. Service berjalan dari Batch D: Apache pid 16664 (`:80`,`:443`), mysqld
pid 22052 (`127.0.0.1:3306`, tanpa 33060). Tidak ada dependency/kode proyek lain yang diubah,
kecuali satu perbaikan deprecation di F4 (di bawah, dengan backup).

| Proyek | `php artisan --version` | platform check | route | smoke HTTP | test (`--compact`) |
|---|---|---|---|---|---|
| `klinik-kasir` | Laravel 13.5.0 | 19/19 `success`, exit 0 | 59 | `/` → 302, `/login` → **200** (6.591 B) | **24 failed / 1 passed** — WIP operator (lihat bawah) |
| `sistem-pembayaran-klinik` | Laravel 13.9.0 | 19/19 `success`, exit 0 | 50 | `/` → 302, `/login` → **500 `Vite manifest not found`** | 1 failed / 1 passed (`ExampleTest`: `/` → 302) |
| `web-igrgto` | Laravel 13.2.0 | 19/19 `success`, exit 0 | 52 | `/` → 302, `/login` → **200** (13.410 B) | tidak dijalankan (validasi cepat, keputusan operator) |
| `yayasan-bhakti-abu-rohana` | Laravel 12.58.0 | 19/19 `success`, exit 0 | 59 | `/` → **200** (25.711 B) | **2 passed**, exit 0 (setelah perbaikan F4) |

**F1 — `klinik-kasir` (kegagalan bukan regresi PHP).** Semua 23 kegagalan SQL identik:
`SQLSTATE[HY000]: General error: 1 duplicate column name: patient_name (Connection: sqlite,
Database: :memory:)` pada `alter table … add column patient_name`. Penyebabnya konflik antar
migrasi milik operator: `database/migrations/2026_04_20_153207_create_transactions_table.php`
(status `M`, sedang diubah operator) sudah menambah `patient_name`, sementara
`database/migrations/2026_05_18_052722_refactor_transactions_patient_name.php` (untracked) juga
menambahkannya. Migrasi status DB dev normal: 12 `Ran`, 0 pending.

Uji pembanding dilakukan dengan meminjam sementara isi migrasi **dari Git HEAD** (aset yang
sudah aman di Git, bukan perubahan baru): hasil **6 failed / 19 passed (48 assertions)** —
sisa kegagalan adalah suite bawaan yang tidak disesuaikan (`ExampleTest` mengharapkan 200 di `/`
yang me-redirect 302, `ProfileTest` mengharapkan `/profile` padahal 404), bukan deprecation
maupun fatal 8.5. **Working tree operator dipulihkan persis**: `git diff --stat` kembali ke 4
berkas semula + 1 untracked, `patient_name` kembali ada di migrasi operator (diverifikasi dengan
`git reset` sehingga perubahan tetap unstaged seperti aslinya).

**F2 — `sistem-pembayaran-klinik`.** `/login` → HTTP 500 dari framework: *`Vite manifest not
found at: D:\laragon\www\sistem-pembayaran-klinik\public\build/manifest.json`*; `public/build`
tidak ada (tiga proyek lain punya). Ini artefak frontend yang belum di-build (`npm run build`),
bukan gejala runtime. Karena halaman tidak pernah dirender, smoke test UI belum bisa dipakai
sebagai bukti; dicatat sebagai blocker/keterbatasan sesuai aturan gate.

**F4 — `yayasan-bhakti-abu-rohana` (satu regresi 8.5 nyata, diperbaiki).** Sebelum perbaikan:
`php artisan --version` mencetak dua baris
`Deprecated: Constant PDO::MYSQL_ATTR_SSL_CA is deprecated since 8.5, use Pdo\Mysql::ATTR_SSL_CA
instead in …\config\database.php on line 61` (dan baris 81). Config lokal ini masih memakai
`PDO::MYSQL_ATTR_SSL_CA`, sedangkan template `vendor/laravel/framework/config/database.php`
sudah `use Pdo\Mysql;` + `Mysql::ATTR_SSL_CA`. Perbaikan minimal (2 baris + 1 `use`), backup:
`batch-f/f4-config-database.php.bak`. Sesudah: `php -l` OK, `artisan --version` → 12.58.0 tanpa
deprecation, `php artisan test --compact` → **2 passed (2 assertions)**, exit 0, `GET /` 200.

**Log Apache.** `…\httpd-2.4.66…\logs\error_log`: entri terakhir tetap
`[Sat Nov 14 00:18:08 2026] … Apache/2.4.66 (Win64) OpenSSL/3.5.9 configured -- resuming normal
operations` + child 17528; **tidak ada** entri baru, warning, atau fatal dari seluruh smoke test
Batch F. Tidak ada `Deprecated:` di HTML keempat proyek (grep = 0).

| Berkas bukti (`…-pre-runtime-upgrade/batch-f/`) | Isi |
|---|---|
| `f1-platform.txt`, `f1-routes.txt`, `f1-migrate.txt`, `f1-artisan.txt`, `f1-php.txt` | F1 platform/rute/DB/versi |
| `f1-tests.txt`, `f1-tests-final.txt` | F1 test dengan WIP operator (24 failed) |
| `f1-tests-clean.txt` | F1 test pembanding migrasi HEAD (6 failed / 19 passed) |
| `sist-platform.txt`, `sist-routes.txt`, `sist-tests.txt` | F2 |
| `web--platform.txt`, `web--routes.txt` | F3 |
| `yaya-platform.txt`, `yaya-routes.txt`, `yaya-tests.txt` | F4 |
| `f4-config-database.php.bak` | Config `database.php` F4 sebelum perbaikan |

**Kesimpulan jujur Gate F → G:** tidak ada regresi **PHP 8.5** yang menggagalkan proyek;
satu-satunya regresi runtime (F4) sudah diperbaiki dan terverifikasi. Gate belum 100% hijau
karena dua blocker non-runtime milik proyek: WIP migrasi `klinik-kasir` (butuh putusan operator)
dan `public/build` `sistem-pembayaran-klinik` yang belum di-build. Keduanya tidak menambah
exposure CVE dan tidak menghalangi runtime patched yang sudah aktif.


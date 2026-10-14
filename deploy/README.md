# Deploy Kolam Prima ke Oracle Cloud Always Free

Panduan lengkap menaruh situs **Kolam Prima** di VM Oracle Cloud (Always Free),
memakai nginx + PHP-FPM + MySQL + queue worker.

Ringkasan stack:

| Komponen | Pilihan |
| --- | --- |
| VM | Oracle `VM.Standard.A1.Flex` — 2 OCPU / 12 GB (ARM), Ubuntu 24.04 |
| Web server | nginx + PHP-FPM 8.3 |
| Database | MySQL 8 (di VM yang sama) |
| Queue | Laravel `queue:work` via supervisor (driver `database`) |
| Media | Disk lokal persisten `storage/app/public/media` (29MB) |
| SSL | Let's Encrypt via certbot (saat domain siap) |

> **Catatan Laravel 13**: `laravel/framework ^13` butuh PHP **^8.3**. Ubuntu 24.04
> menyediakan PHP 8.3 di repo resmi, jadi cukup.

---

## Ringkasan alur

```
[1] Buat VM di Oracle Console
        │
        ▼
[2] sudo bash deploy/setup-server.sh          # di server (sekali saja)
        │
        ▼
[3] bash deploy/push-media.sh <IP>            # dari komputer lokal
        │
        ▼
[4] sudo bash deploy/deploy.sh --seed         # di server
        │
        ▼
[5] Buka http://<IP>  →  selesai
```

---

## 1. Buat VM di Oracle Cloud

1. Daftar **Oracle Cloud Free Tier** (perlu kartu untuk verifikasi; resource
   *Always Free* tidak ditagih).
2. Pilih **Home Region: Singapore**. ⚠️ Home region **permanen**, tidak bisa diubah.
3. **Compute → Instances → Create instance**:
   - **Image**: Ubuntu 24.04
   - **Shape**: Ampere → `VM.Standard.A1.Flex`, **2 OCPU / 12 GB**
   - **SSH keys**: pilih *Paste public key*, tempel isi
     `~/.ssh/kolam_prima_oracle.pub` (dibuat di langkah 0 di bawah)
   - Boot volume: default (≥47 GB)
4. **Networking**: buat VCN baru (default OK).
5. Setelah VM jalan, catat **Public IP** dan username (**ubuntu**).
6. **Buka port 80 & 443**: VCN → Security List (atau NSG) → Add Ingress Rules:
   - Source `0.0.0.0/0`, IP Protocol **TCP**, Destination Port `80`
   - Source `0.0.0.0/0`, IP Protocol **TCP**, Destination Port `443`

> ⚠️ Oracle juga memakai **firewall di dalam VM** (iptables) yang memblokir
> port selain 22. Buka port 80/443 di dalam VM:
> ```bash
> sudo iptables -I INPUT 6 -m state --state NEW -p tcp --dport 80 -j ACCEPT
> sudo iptables -I INPUT 6 -m state --state NEW -p tcp --dport 443 -j ACCEPT
> sudo netfilter-persistent save
> ```

---

## 0. Siapkan kunci SSH (di komputer lokal)

```bash
ssh-keygen -t ed25519 -C "kolam-prima-oracle" -f ~/.ssh/kolam_prima_oracle -N ""
cat ~/.ssh/kolam_prima_oracle.pub      # tempel ke Oracle
```

Uji koneksi:

```bash
ssh -i ~/.ssh/kolam_prima_oracle ubuntu@<IP_SERVER>
```

---

## 2. Setup server (sekali saja)

Di server, sebagai root:

```bash
git clone https://github.com/ItsMeAldy/kolam-prima.git /var/www/kolam-prima
sudo bash /var/www/kolam-prima/deploy/setup-server.sh
```

Skrip akan menginstall semua paket, membuat database + user (password acak),
menulis `.env` produksi, build aset, migrate, dan memasang nginx + supervisor.

**Catat `DB_PASS` yang ditampilkan di akhir** (juga tersimpan di `.env` server).

> Bila ingin password DB sendiri:
> `sudo DB_PASS='rahasia' bash /var/www/kolam-prima/deploy/setup-server.sh`
> Bila ingin APP_URL sendiri:
> `sudo APP_URL='https://domain.com' bash /var/www/kolam-prima/deploy/setup-server.sh`

---

## 3. Kirim media (dari komputer lokal)

Media **tidak ada di git**, jadi harus dikirim terpisah:

```bash
bash deploy/push-media.sh <IP_SERVER>
```

---

## 4. Migrasi + seed data

Di server (setelah media terkirim, agar gambar proyek/video ter-link):

```bash
sudo bash /var/www/kolam-prima/deploy/deploy.sh --seed
```

Login admin: `/admin` — email `admin@kolamprima.test`, password `password`
(**ganti segera** lewat panel admin).

---

## 5. Update deploy berikutnya

Setelah `git push` dari lokal, di server cukup:

```bash
sudo bash /var/www/kolam-prima/deploy/deploy.sh
```

---

## SSL + domain kustom

Setelah domain diarahkan (A record → IP Oracle):

```bash
sudo apt-get install -y certbot python3-certbot-nginx
sudo certbot --nginx -d domain-anda.com -d www.domain-anda.com
```

Lalu perbarui `APP_URL` di `/var/www/kolam-prima/.env` menjadi `https://domain-anda.com`
dan jalankan `sudo -u www-data php /var/www/kolam-prima/artisan optimize`.

---

## Catatan penting

- **Reclaim VM**: Oracle dapat menghapus VM *Always Free* yang dianggap idle
  (>7 hari, CPU/mem/jaringan rendah). Untuk situs traffic rendah, **disarankan
  upgrade ke Pay As You Go** — resource *Always Free* tetap $0, tetapi
  kebijakan reclaim tidak berlaku. Pasang juga **Budget Alert**.
- **Kapasitas ARM**: bila muncul *"out of host capacity"*, coba Availability
  Domain lain, atau ulangi beberapa saat kemudian.
- **Queue**: driver `database`, worker dijalankan supervisor. Untuk situs tanpa
  job, ini aman (worker idle).
- **Backup**: pertimbangkan `mysqldump` berkala + backup folder media.

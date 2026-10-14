#!/usr/bin/env bash
#
# Provision Ubuntu 24.04 (Oracle Cloud Always Free, ARM) + deploy Kolam Prima.
#
# Jalankan di server sebagai root:
#     sudo bash deploy/setup-server.sh
#
# Skrip ini idempoten: aman dijalankan ulang.
# Media (foto/video) TIDAK dikirim oleh skrip ini — lihat deploy/push-media.sh.
#
set -euo pipefail

# ---------------------------------------------------------------------------
# Konfigurasi
# ---------------------------------------------------------------------------
APP_NAME="kolam-prima"
APP_DIR="/var/www/${APP_NAME}"
REPO_URL="https://github.com/ItsMeAldy/kolam-prima.git"
BRANCH="main"

DB_NAME="kolam_prima"
DB_USER="kolam"
# Password DB di-generate acak bila tidak di-set lewat env:
#     sudo DB_PASS='...' bash deploy/setup-server.sh
DB_PASS="${DB_PASS:-$(openssl rand -hex 16)}"

PHP_VER="8.3"
NODE_MAJOR="20"

# APP_URL: pakai APP_URL dari env bila ada, kalau tidak deteksi IP publik.
APP_URL="${APP_URL:-}"

log() { echo -e "\n\033[1;36m==> $*\033[0m"; }

# ---------------------------------------------------------------------------
# 0. Prasyarat
# ---------------------------------------------------------------------------
if [ "$(id -u)" -ne 0 ]; then
    echo "Harus dijalankan sebagai root (pakai sudo)." >&2
    exit 1
fi

if [ -z "${APP_URL}" ]; then
    PUBLIC_IP="$(curl -fsS --max-time 5 https://ifconfig.me 2>/dev/null || true)"
    APP_URL="http://${PUBLIC_IP:-localhost}"
fi

log "APP_DIR   = ${APP_DIR}"
log "APP_URL   = ${APP_URL}"
log "DB        = ${DB_NAME} / ${DB_USER}"
log "PHP       = ${PHP_VER}, Node = ${NODE_MAJOR}"

# ---------------------------------------------------------------------------
# 1. Paket dasar + PHP + nginx + MySQL + Node + Composer
# ---------------------------------------------------------------------------
log "apt update & upgrade"
export DEBIAN_FRONTEND=noninteractive
apt-get update -y
apt-get upgrade -y

log "Install paket dasar"
apt-get install -y \
    git unzip curl ca-certificates gnupg lsb-release \
    nginx mysql-server supervisor \
    php${PHP_VER}-fpm php${PHP_VER}-cli php${PHP_VER}-mbstring php${PHP_VER}-xml \
    php${PHP_VER}-curl php${PHP_VER}-zip php${PHP_VER}-mysql php${PHP_VER}-gd \
    php${PHP_VER}-intl php${PHP_VER}-bcmath php${PHP_VER}-opcache php${PHP_VER}-sqlite3

log "Install Node.js ${NODE_MAJOR}"
if ! command -v node >/dev/null 2>&1 || [ "$(node -v | cut -c2- | cut -d. -f1)" -lt "${NODE_MAJOR}" ]; then
    curl -fsSL "https://deb.nodesource.com/setup_${NODE_MAJOR}.x" | bash -
    apt-get install -y nodejs
fi

log "Install Composer (terbaru)"
if ! command -v composer >/dev/null 2>&1; then
    curl -fsSL https://getcomposer.org/installer -o /tmp/composer-setup.php
    php /tmp/composer-setup.php --install-dir=/usr/local/bin --filename=composer
    rm -f /tmp/composer-setup.php
fi

# ---------------------------------------------------------------------------
# 2. MySQL: database + user
# ---------------------------------------------------------------------------
log "Siapkan database MySQL"
systemctl enable --now mysql

mysql <<SQL
CREATE DATABASE IF NOT EXISTS \`${DB_NAME}\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS '${DB_USER}'@'localhost' IDENTIFIED BY '${DB_PASS}';
ALTER USER '${DB_USER}'@'localhost' IDENTIFIED BY '${DB_PASS}';
GRANT ALL PRIVILEGES ON \`${DB_NAME}\`.* TO '${DB_USER}'@'localhost';
FLUSH PRIVILEGES;
SQL

# ---------------------------------------------------------------------------
# 3. Ambil kode aplikasi
# ---------------------------------------------------------------------------
log "Clone / update repositori"
mkdir -p /var/www
if [ -d "${APP_DIR}/.git" ]; then
    git -C "${APP_DIR}" fetch --all --prune
    git -C "${APP_DIR}" checkout "${BRANCH}"
    git -C "${APP_DIR}" pull --ff-only origin "${BRANCH}"
else
    git clone --branch "${BRANCH}" "${REPO_URL}" "${APP_DIR}"
fi

# ---------------------------------------------------------------------------
# 4. Tulis .env produksi (hanya bila belum ada, agar tidak menimpa rahasia)
# ---------------------------------------------------------------------------
if [ ! -f "${APP_DIR}/.env" ]; then
    log "Tulis .env produksi"
    cat > "${APP_DIR}/.env" <<EOF
APP_NAME="Kolam Prima"
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_URL=${APP_URL}

APP_LOCALE=id
APP_FALLBACK_LOCALE=id
APP_FAKER_LOCALE=id_ID

LOG_CHANNEL=stack
LOG_STACK=single
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=error

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=${DB_NAME}
DB_USERNAME=${DB_USER}
DB_PASSWORD=${DB_PASS}

SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_ENCRYPT=false
SESSION_PATH=/
SESSION_DOMAIN=null

BROADCAST_CONNECTION=log
FILESYSTEM_DISK=local
QUEUE_CONNECTION=database

CACHE_STORE=database

MAIL_MAILER=log
MAIL_FROM_ADDRESS="hello@example.com"
MAIL_FROM_NAME="\${APP_NAME}"

VITE_APP_NAME="\${APP_NAME}"
EOF
else
    log ".env sudah ada — dilewati (tidak menimpa)"
fi

# ---------------------------------------------------------------------------
# 5. Build aplikasi
# ---------------------------------------------------------------------------
log "composer install"
composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader -d "${APP_DIR}"

log "npm ci & build aset frontend"
( cd "${APP_DIR}" && npm ci && npm run build )

log "Set kepemilikan berkas"
chown -R www-data:www-data "${APP_DIR}"

log "APP_KEY + storage:link + migrate"
sudo -u www-data php "${APP_DIR}/artisan" key:generate --force
sudo -u www-data php "${APP_DIR}/artisan" storage:link || true
sudo -u www-data php "${APP_DIR}/artisan" migrate --force

# ---------------------------------------------------------------------------
# 6. nginx
# ---------------------------------------------------------------------------
log "Pasang konfigurasi nginx"
cp "${APP_DIR}/deploy/nginx/kolam-prima.conf" /etc/nginx/sites-available/kolam-prima.conf
ln -sf /etc/nginx/sites-available/kolam-prima.conf /etc/nginx/sites-enabled/kolam-prima.conf
rm -f /etc/nginx/sites-enabled/default
nginx -t
systemctl enable --now nginx
systemctl reload nginx

# ---------------------------------------------------------------------------
# 7. Queue worker (supervisor)
# ---------------------------------------------------------------------------
log "Pasang queue worker (supervisor)"
cp "${APP_DIR}/deploy/supervisor/kolam-prima-worker.conf" /etc/supervisor/conf.d/kolam-prima-worker.conf
supervisorctl reread
supervisorctl update
supervisorctl start kolam-prima-worker:* || true

# ---------------------------------------------------------------------------
# 8. Optimasi
# ---------------------------------------------------------------------------
log "Optimasi Laravel"
sudo -u www-data php "${APP_DIR}/artisan" optimize

log "SELESAI."
cat <<EOF

------------------------------------------------------------
Setup server selesai.

  APP_URL : ${APP_URL}
  APP_DIR : ${APP_DIR}
  DB      : ${DB_NAME} / ${DB_USER}
  DB_PASS : ${DB_PASS}
  .env    : ${APP_DIR}/.env

Langkah berikutnya (PENTING, urutannya):
  1. Dari komputer lokal, kirim media (29MB):
         bash deploy/push-media.sh <IP_SERVER>
  2. Di server, jalankan deploy untuk migrate + seed (mengaitkan gambar):
         sudo bash ${APP_DIR}/deploy/deploy.sh

Setelah domain kustom siap, jalankan:
  sudo certbot --nginx -d domain-anda.com -d www.domain-anda.com
------------------------------------------------------------
EOF

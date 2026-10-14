#!/usr/bin/env bash
#
# Deploy / update Kolam Prima di server (idempoten, aman diulang).
#
# Jalankan di server sebagai root:
#     sudo bash /var/www/kolam-prima/deploy/deploy.sh
#
# Untuk environment pertama kali, jalankan dengan --seed agar data contoh terisi:
#     sudo bash /var/www/kolam-prima/deploy/deploy.sh --seed
#
# Catatan: media (foto/video) harus sudah dikirim lebih dulu lewat
# deploy/push-media.sh, supaya seeder bisa mengaitkan path gambarnya.
#
set -euo pipefail

APP_DIR="/var/www/kolam-prima"
BRANCH="main"

SEED=false
if [ "${1:-}" = "--seed" ]; then
    SEED=true
fi

log() { echo -e "\n\033[1;36m==> $*\033[0m"; }

if [ "$(id -u)" -ne 0 ]; then
    echo "Harus dijalankan sebagai root (pakai sudo)." >&2
    exit 1
fi

cd "${APP_DIR}"

log "Tarik kode terbaru (${BRANCH})"
git fetch --all --prune
git checkout "${BRANCH}"
git pull --ff-only origin "${BRANCH}"

log "composer install (tanpa dev)"
composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader

log "Build aset frontend"
npm ci
npm run build

log "Kepemilikan berkas"
chown -R www-data:www-data "${APP_DIR}"

log "Migrasi database"
sudo -u www-data php artisan migrate --force

if [ "${SEED}" = true ]; then
    log "Seed data (idempoten: updateOrCreate)"
    sudo -u www-data php artisan db:seed --force
fi

log "storage:link"
sudo -u www-data php artisan storage:link || true

log "Bersihkan & bangun ulang cache"
sudo -u www-data php artisan optimize:clear
sudo -u www-data php artisan optimize

log "Restart queue worker"
supervisorctl restart kolam-prima-worker:* || true

log "Reload nginx"
nginx -t && systemctl reload nginx

log "SELESAI. Deploy selesai."

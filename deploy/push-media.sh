#!/usr/bin/env bash
#
# Kirim media lokal (foto + video, ~29MB) ke server Oracle.
# Dijalankan dari KOMPUTER LOKAL (bukan di server).
#
#     bash deploy/push-media.sh <IP_SERVER> [USER] [SSH_KEY]
#
# Contoh:
#     bash deploy/push-media.sh 152.70.1.2
#     bash deploy/push-media.sh 152.70.1.2 ubuntu ~/.ssh/kolam_prima_oracle
#
set -euo pipefail

SERVER="${1:-}"
USER="${2:-ubuntu}"
SSH_KEY="${3:-$HOME/.ssh/kolam_prima_oracle}"

if [ -z "${SERVER}" ]; then
    echo "Pakai: bash deploy/push-media.sh <IP_SERVER> [USER] [SSH_KEY]" >&2
    exit 1
fi

LOCAL_MEDIA="storage/app/public/media"
REMOTE_DIR="/var/www/kolam-prima/storage/app/public"

if [ ! -d "${LOCAL_MEDIA}" ]; then
    echo "Folder media lokal tidak ditemukan: ${LOCAL_MEDIA}" >&2
    exit 1
fi

SSH_OPTS=(-i "${SSH_KEY}" -o StrictHostKeyChecking=accept-new)
SSH="ssh ${SSH_OPTS[*]} ${USER}@${SERVER}"

echo "==> Siapkan folder tujuan di server"
${SSH} "sudo mkdir -p ${REMOTE_DIR}/media && sudo chown -R www-data:www-data ${REMOTE_DIR}"

echo "==> Kirim media (tar lewat ssh, tanpa rsync)"
tar -C "${LOCAL_MEDIA}" -czf - . \
    | ${SSH} "sudo tar -xzf - -C ${REMOTE_DIR}/media"

echo "==> Rapikan kepemilikan"
${SSH} "sudo chown -R www-data:www-data ${REMOTE_DIR}/media && \
        sudo -u www-data php /var/www/kolam-prima/artisan storage:link || true"

echo "==> Selesai. Media terkirim ke ${SERVER}:${REMOTE_DIR}/media"
echo "    Lanjutkan di server: sudo bash /var/www/kolam-prima/deploy/deploy.sh --seed"

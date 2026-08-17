#!/usr/bin/env bash
# DBバックアップ（mysqldump→gzip→ローテーション、任意でCloudflare R2へオフサイト保管）
# 配置先: /var/www/syaroushi/deploy/backup.sh（このリポジトリをcloneした場所にそのまま置いてよい）
# cron: 0 3 * * * /var/www/syaroushi/deploy/backup.sh >> /var/log/syaroushi-backup.log 2>&1
#
# 顧問先の個人情報を含むダンプのため、BACKUP_DIRは他ユーザーから読めないパーミッションにすること。

set -euo pipefail

APP_DIR="/var/www/syaroushi"
BACKUP_DIR="/var/backups/syaroushi"
RETENTION_DAYS=14
TIMESTAMP=$(date +%Y%m%d-%H%M%S)

set -a
# shellcheck source=/dev/null
source "$APP_DIR/.env"
set +a

mkdir -p "$BACKUP_DIR"
chmod 700 "$BACKUP_DIR"

DUMP_FILE="$BACKUP_DIR/db-${TIMESTAMP}.sql.gz"

export MYSQL_PWD="${DB_PASSWORD}"
mysqldump --single-transaction --quick \
    --host="${DB_HOST}" --port="${DB_PORT}" --user="${DB_USERNAME}" \
    "${DB_DATABASE}" | gzip > "$DUMP_FILE"
unset MYSQL_PWD

chmod 600 "$DUMP_FILE"

# 保持期間を過ぎた古いバックアップを削除
find "$BACKUP_DIR" -name 'db-*.sql.gz' -mtime "+${RETENTION_DAYS}" -delete

# 任意: Cloudflare R2へのオフサイトコピー（同一VPSが壊れた場合の保険）。
# .envにBACKUP_R2_BUCKET等を追加し、事前に
#   aws configure set aws_access_key_id "$BACKUP_R2_ACCESS_KEY_ID" --profile r2-backup
#   aws configure set aws_secret_access_key "$BACKUP_R2_SECRET_ACCESS_KEY" --profile r2-backup
# でプロファイルを用意しておけば有効になる（未設定ならこのブロックはスキップされる）。
# 本番の書類/レポート保存用バケットとは分け、バックアップ専用バケットを別途作成すること。
if [ -n "${BACKUP_R2_BUCKET:-}" ]; then
    aws s3 cp "$DUMP_FILE" "s3://${BACKUP_R2_BUCKET}/db/$(basename "$DUMP_FILE")" \
        --endpoint-url "${BACKUP_R2_ENDPOINT:-$AWS_ENDPOINT}" --profile r2-backup
fi

echo "[$(date '+%F %T')] backup ok: ${DUMP_FILE}"

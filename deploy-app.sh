#!/usr/bin/env bash

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd -P)"
DEST_DIR="${1:-${DEST_DIR:-/var/www/esco-apps-redirector/}}"
APP_NAME_DIR="${APP_NAME_DIR:-$(basename "$DEST_DIR")}"

RSYNC_OPTS=(-a --delete)
if [ "${DRY_RUN:-1}" != "0" ]; then
  RSYNC_OPTS+=(--dry-run --itemize-changes)
fi

# 1. Backup complet du répertoire déployé (à supprimer manuellement une fois
#    le déploiement validé)
BACKUP_DIR="${BACKUP_DIR:-$(dirname "$DEST_DIR")/${APP_NAME_DIR}-backup-$(date +%Y%m%d-%H%M%S)}"
if [ "${DRY_RUN:-1}" = "0" ] && [ -d "$DEST_DIR" ]; then
  echo "Backup du répertoire : $DEST_DIR -> $BACKUP_DIR"
  mkdir -p "$(dirname "$BACKUP_DIR")"
  cp -a "$DEST_DIR" "$BACKUP_DIR"
  echo "ATTENTION : pensez à supprimer le répertoire de backup $BACKUP_DIR"
fi

if [ "${DRY_RUN:-1}" = "0" ]; then
  mkdir -p "$DEST_DIR"
fi

rsync "${RSYNC_OPTS[@]}" \
  --exclude='.git/' \
  --exclude='.github/' \
  --exclude='conf/conf.inc.php' \
  --exclude='conf/conf.inc.test.php' \
  --exclude='conf/cas.inc.php' \
  --exclude='conf/cas-test.inc.php' \
  --exclude='conf/*.bkp.*' \
  --exclude='logs/*' \
  "$SCRIPT_DIR/" \
  "$DEST_DIR"

if [ "${DRY_RUN:-1}" = "0" ]; then
  mkdir -p "$DEST_DIR/logs"
fi

#!/usr/bin/env bash

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd -P)"
DEST_DIR="${1:-${DEST_DIR:-/var/www/esco-apps-redirector/}}"

RSYNC_OPTS=(-a --delete)
if [ "${DRY_RUN:-0}" = "1" ]; then
  RSYNC_OPTS+=(--dry-run --itemize-changes)
fi

mkdir -p "$DEST_DIR"

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

mkdir -p "$DEST_DIR/logs"

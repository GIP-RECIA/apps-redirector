#!/usr/bin/env bash
#
# deploy-app.sh : déploie le contenu de ce répertoire vers un répertoire cible
# (par défaut /var/www/esco-apps-redirector/).
#
# Usage :
#   ./deploy-app.sh [DEST_DIR]          # mode dry-run par défaut : simulation seule
#   DRY_RUN=0 ./deploy-app.sh [DEST_DIR]  # déploiement réel (avec backup préalable)
#
# Variables d'environnement :
#   DEST_DIR      : répertoire cible du déploiement (ou 1er argument)
#   APP_NAME_DIR  : nom du répertoire utilisé pour le backup (défaut : basename DEST_DIR)
#   BACKUP_DIR    : chemin complet du backup (défaut : <parent DEST_DIR>/<APP_NAME_DIR>-backup-<timestamp>)
#   DRY_RUN       : 1 (défaut) = simulation, 0 = déploiement réel
#
# Étapes :
#   0. Configuration du mode dry-run et des options rsync
#   1. Backup complet du répertoire déployé (réel uniquement, à supprimer manuellement)
#   2. Création du répertoire cible si besoin (réel uniquement)
#   3. Synchronisation via rsync avec exclusions (conf locales, logs, .git...)
#   4. Création du répertoire logs sur la cible (réel uniquement)

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd -P)"
DEST_DIR="${1:-${DEST_DIR:-/var/www/esco-apps-redirector/}}"
APP_NAME_DIR="${APP_NAME_DIR:-$(basename "$DEST_DIR")}"

# Étape 0. Mode dry-run activé par défaut : on liste les changements
#         (--itemize-changes) sans rien modifier.
RSYNC_OPTS=(-a --delete)
if [ "${DRY_RUN:-1}" != "0" ]; then
  echo "MODE DRY-RUN : aucun fichier ne sera modifié, simulation seule (utilisez DRY_RUN=0 pour déployer réellement)."
  RSYNC_OPTS+=(--dry-run --itemize-changes)
fi

# 1. Backup complet du répertoire déployé (réel uniquement ; à supprimer
#    manuellement une fois le déploiement validé)
BACKUP_DIR="${BACKUP_DIR:-$(dirname "$DEST_DIR")/${APP_NAME_DIR}-backup-$(date +%Y%m%d-%H%M%S)}"
if [ "${DRY_RUN:-1}" = "0" ] && [ -d "$DEST_DIR" ]; then
  echo "Backup du répertoire : $DEST_DIR -> $BACKUP_DIR"
  mkdir -p "$(dirname "$BACKUP_DIR")"
  cp -a "$DEST_DIR" "$BACKUP_DIR"
  echo "ATTENTION : pensez à supprimer le répertoire de backup $BACKUP_DIR"
fi

# 2. Création du répertoire cible si nécessaire (réel uniquement)
if [ "${DRY_RUN:-1}" = "0" ]; then
  mkdir -p "$DEST_DIR"
fi

# 3. Synchronisation du code vers la cible :
#    - en dry-run : simple affichage des changements qui seraient appliqués
#    - en réel : copie + suppression (--delete) des fichiers obsolètes
#    Exclusions : VCS, confs locales/environnement et logs de la cible,
#    plus fichiers gérés hors de ce dépôt présents sur la cible (protégés
#    du --delete grâce aux --exclude).
rsync "${RSYNC_OPTS[@]}" \
  --exclude='.git/' \
  --exclude='.github/' \
  --exclude='conf/conf.inc.php' \
  --exclude='conf/conf.inc.test.php' \
  --exclude='conf/cas.inc.php' \
  --exclude='conf/cas-test.inc.php' \
  --exclude='conf/*.bkp.*' \
  --exclude='logs/*' \
  --exclude='getAllInfos.php' \
  --exclude='getGroupsJson.php' \
  --exclude='getInfosJson.php' \
  --exclude='getStatsJson.php' \
  --exclude='loading-spinner.js' \
  --exclude='conf/getYpareos.sh' \
  "$SCRIPT_DIR/" \
  "$DEST_DIR"

# 4. Création du répertoire logs sur la cible (exclu du rsync, réel uniquement)
if [ "${DRY_RUN:-1}" = "0" ]; then
  mkdir -p "$DEST_DIR/logs"
fi

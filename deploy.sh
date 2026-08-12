#!/usr/bin/env bash
# =============================================================================
#  deploy.sh — Déploiement du backend Gestion Scolaire sur Hostinger (SSH)
#  Usage :  ./deploy.sh [--migrate] [--seed] [--no-composer]
#
#  Prérequis :
#    - Clé SSH configurée : ~/.ssh/gestion_scolaire_deploy
#    - Domaine api.agascom.com → docroot = ~/domains/api.agascom.com/public_html
# =============================================================================
set -euo pipefail

# --- Configuration -----------------------------------------------------------
HOST="153.92.220.242"
PORT="65002"
USER="u650924616"
KEY="${SSH_KEY:-$HOME/.ssh/gestion_scolaire_deploy}"
REMOTE_DIR="gestion-api"                    # ~/gestion-api
DOCROOT="domains/api.agascom.com/public_html"

DO_MIGRATE=false
DO_SEED=false
DO_COMPOSER=true

for arg in "$@"; do
  case "$arg" in
    --migrate)    DO_MIGRATE=true ;;
    --seed)       DO_SEED=true; DO_MIGRATE=true ;;
    --no-composer) DO_COMPOSER=false ;;
    *)            echo "Argument inconnu : $arg" >&2; exit 1 ;;
  esac
done

SSH="ssh -i $KEY -p $PORT $USER@$HOST"
SCP="scp -i $KEY -P $PORT"

echo "=== 1/4 Déploiement du code (git pull) ==="
$SSH "cd ~/$REMOTE_DIR && git fetch origin main && git reset --hard origin/main" 

if [ "$DO_COMPOSER" = true ]; then
  echo "=== 2/4 Composer (--no-scripts : contourne proc_open) ==="
  $SSH "cd ~/$REMOTE_DIR && composer install --prefer-dist --no-dev --no-scripts --no-interaction --optimize-autoloader"
else
  echo "=== 2/4 Composer sauté (--no-composer) ==="
fi

echo "=== 3/4 Permissions + lien storage ==="
$SSH "cd ~/$REMOTE_DIR && chmod -R 775 storage bootstrap/cache && ln -sfn ~/$REMOTE_DIR/storage/app/public $DOCROOT/storage"

if [ "$DO_MIGRATE" = true ]; then
  if [ "$DO_SEED" = true ]; then
    echo "=== 4/4 Migrations + seed ==="
    $SSH "cd ~/$REMOTE_DIR && php artisan migrate --force && php artisan db:seed --force"
  else
    echo "=== 4/4 Migrations ==="
    $SSH "cd ~/$REMOTE_DIR && php artisan migrate --force"
  fi
else
  echo "=== 4/4 Migrations ignorées (ajoutez --migrate ou --seed) ==="
fi

echo "=== Post-install Laravel (package:discover + caches) ==="
$SSH "cd ~/$REMOTE_DIR && php artisan package:discover && php artisan config:cache && php artisan route:cache"

echo "=== Santé de l'API ==="
curl -s -o /dev/null -w "HTTP %{http_code}\n" https://api.agascom.com/api/v1/auth/login || true

echo "=== Déploiement terminé ✅ ==="
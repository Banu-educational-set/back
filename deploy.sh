#!/usr/bin/env bash
# Deploy local changes to VPS.
# Usage: ./deploy.sh                  # sync code + clear config cache
#        ./deploy.sh migrate          # also run migrations
#        ./deploy.sh composer         # also run `composer install`
#        ./deploy.sh full             # composer + migrate + clear all caches
set -euo pipefail

VPS="root@109.122.252.86"
PROJECT_DIR="/opt/banu"
LOCAL_DIR="$(cd "$(dirname "$0")" && pwd)"

echo "==> Syncing code to $VPS:$PROJECT_DIR"
rsync -avz --delete \
  --exclude='vendor' \
  --exclude='node_modules' \
  --exclude='.git' \
  --exclude='.env' \
  --exclude='public/storage' \
  --exclude='storage/app/public/**' \
  --exclude='storage/app/private/**' \
  --exclude='storage/logs/*' \
  --exclude='storage/framework/cache/*' \
  --exclude='storage/framework/sessions/*' \
  --exclude='storage/framework/views/*' \
  --exclude='bootstrap/cache/*.php' \
  --exclude='deploy.sh' \
  --exclude='composer.lock.bak' \
  -e ssh \
  "$LOCAL_DIR/" \
  "$VPS:$PROJECT_DIR/"

MODE="${1:-default}"

case "$MODE" in
  composer|full)
    echo "==> Installing composer dependencies"
    ssh "$VPS" "cd $PROJECT_DIR && docker compose exec -T app composer install --no-dev --optimize-autoloader"
    ;;
esac

echo "==> Ensuring storage symlink + writable permissions"
ssh "$VPS" "cd $PROJECT_DIR && docker compose exec -T app sh -c 'php artisan storage:link > /dev/null 2>&1 || true; chown -R www-data:www-data storage bootstrap/cache; chmod -R u+rwX,g+rwX storage bootstrap/cache'"

echo "==> Clearing config cache"
ssh "$VPS" "cd $PROJECT_DIR && docker compose exec -T app php artisan config:clear"

case "$MODE" in
  migrate|full)
    echo "==> Running migrations"
    ssh "$VPS" "cd $PROJECT_DIR && docker compose exec -T app php artisan migrate --force"
    ;;
esac

if [ "$MODE" = "full" ]; then
  echo "==> Clearing route cache"
  ssh "$VPS" "cd $PROJECT_DIR && docker compose exec -T app php artisan route:clear" || true
fi

echo "==> Done."

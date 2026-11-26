#!/usr/bin/env bash
# ----------------------------------------------------------------------------
# Deploys the release packages into a cPanel-like or VPS-like stack using the
# real server scripts, then smoke-tests it (Stage 4 §10.1). Used by CI.
#
# Usage: run.sh <cpanel|vps> <version>
# Expects dist/bizzmag-api-<version>.tar.gz and dist/bizzmag-app-<version>.tar.gz
# (build the app with NEXT_PUBLIC_API_URL=http://api.bizzmag.test:8080).
# ----------------------------------------------------------------------------
set -euo pipefail

PLATFORM="${1:?cpanel or vps}"
VERSION="${2:?version}"
ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
VERIFY="$ROOT/infrastructure/verify"
COMPOSE=(docker compose -f "$VERIFY/compose.yml" --profile "$PLATFORM")

case "$PLATFORM" in
    cpanel)
        SERVICE=cpanel; RUN_AS=salon; DB_HOST=mariadb
        APP_DIR=/home/salon/apps/bizzmag-api; DOCROOT=/home/salon/app.bizzmag.test; WORK=/home/salon/deploy ;;
    vps)
        SERVICE=php; RUN_AS=www-data; DB_HOST=mysql
        APP_DIR=/srv/bizzmag/api; DOCROOT=/srv/bizzmag/app; WORK=/srv/bizzmag/deploy ;;
    *) echo "Unknown platform $PLATFORM" >&2; exit 64 ;;
esac

as_root() { "${COMPOSE[@]}" exec -T -u root "$SERVICE" bash -c "$1"; }
as_user() { "${COMPOSE[@]}" exec -T -u "$RUN_AS" -e APP_DIR="$APP_DIR" -e HOME="${WORK%/deploy}" "$SERVICE" bash -c "$1"; }

echo "==> Starting the $PLATFORM stack"
"${COMPOSE[@]}" up -d --build --wait

echo "==> Uploading release $VERSION"
as_root "mkdir -p $WORK $(dirname "$APP_DIR") $DOCROOT && chown -R $RUN_AS: $(dirname "$APP_DIR") $DOCROOT $WORK"
for file in "infrastructure/server/deploy-api.sh" "infrastructure/server/deploy-app.sh" "dist/bizzmag-api-$VERSION.tar.gz" "dist/bizzmag-app-$VERSION.tar.gz"; do
    "${COMPOSE[@]}" cp "$ROOT/$file" "$SERVICE:$WORK/"
done
as_root "chown -R $RUN_AS: $WORK"

echo "==> First install (expected to stop and create shared/.env)"
if as_user "bash $WORK/deploy-api.sh install $WORK/bizzmag-api-$VERSION.tar.gz"; then
    echo "First install should have stopped to ask for settings." >&2
    exit 1
fi

echo "==> Filling shared/.env as an owner would"
as_user "cd $APP_DIR/shared && sed -i \
    -e 's#^APP_ENV=.*#APP_ENV=staging#' \
    -e 's#^APP_DEBUG=.*#APP_DEBUG=false#' \
    -e 's#^APP_URL=.*#APP_URL=http://api.bizzmag.test:8080#' \
    -e 's#^FRONTEND_URL=.*#FRONTEND_URL=http://app.bizzmag.test:8080#' \
    -e 's#^SANCTUM_STATEFUL_DOMAINS=.*#SANCTUM_STATEFUL_DOMAINS=app.bizzmag.test:8080#' \
    -e 's#^SESSION_DOMAIN=.*#SESSION_DOMAIN=.bizzmag.test#' \
    -e 's#^DB_HOST=.*#DB_HOST=$DB_HOST#' \
    -e 's#^DB_PORT=.*#DB_PORT=3306#' \
    -e 's#^DB_DATABASE=.*#DB_DATABASE=salon_live#' \
    -e 's#^DB_USERNAME=.*#DB_USERNAME=salon_user#' \
    -e 's#^DB_PASSWORD=.*#DB_PASSWORD=verify-password#' .env"

echo "==> Install"
as_user "bash $WORK/deploy-api.sh install $WORK/bizzmag-api-$VERSION.tar.gz"
as_user "bash $WORK/deploy-app.sh $WORK/bizzmag-app-$VERSION.tar.gz $DOCROOT"

echo "==> Running the scheduler once (as cron would)"
as_user "cd $APP_DIR/current && php artisan schedule:run"

echo "==> First-run setup (app:install), scripted with the password in the environment"
as_user "cd $APP_DIR/current && BIZZMAG_OWNER_PASSWORD=Verify-owner-2026 php artisan app:install --business='Verify Salon' --owner-name='Verify Owner' --owner-email=owner@bizzmag.test --no-interaction"

echo "==> Preflight on the deployed release"
as_user "cd $APP_DIR/current && php artisan app:preflight"

bash "$VERIFY/smoke-test.sh" "$VERSION"

echo "==> Redeploy the same release (migrations must be a no-op), then roll back"
as_user "bash $WORK/deploy-api.sh install $WORK/bizzmag-api-$VERSION.tar.gz"
as_user "bash $WORK/deploy-api.sh rollback"
bash "$VERIFY/smoke-test.sh" "$VERSION"

echo "==> $PLATFORM deployment verified."

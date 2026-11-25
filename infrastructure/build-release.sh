#!/usr/bin/env bash
# ----------------------------------------------------------------------------
# Builds the two release packages (Stage 4 v1.1 §10.1):
#
#   dist/bizzmag-api-<version>.tar.gz  Laravel API with production vendor/,
#                                      no .env, no tests, no dev tools.
#   dist/bizzmag-app-<version>.tar.gz  Static web app (frontend/out) with
#                                      .htaccess. Built for ONE API address.
#
# Usage:
#   NEXT_PUBLIC_API_URL=https://api.your-domain infrastructure/build-release.sh 1.0.0
#   infrastructure/build-release.sh 1.0.0 --api-only
#
# The web app has the API address built in, so build it with your real API
# URL. Vercel builds the web app itself and does not need this package.
# Requires: php 8.3+, composer, node 22+, pnpm, tar, rsync.
# ----------------------------------------------------------------------------
set -euo pipefail

VERSION="${1:-}"
MODE="${2:-}"

if [[ -z "$VERSION" ]]; then
    echo "Usage: build-release.sh <version> [--api-only|--app-only]" >&2
    exit 64
fi

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
DIST="$ROOT/dist"
STAGE="$(mktemp -d)"
trap 'rm -rf "$STAGE"' EXIT

mkdir -p "$DIST"

build_api() {
    echo "==> Building API package $VERSION"
    mkdir -p "$STAGE/api"

    rsync -a "$ROOT/backend/" "$STAGE/api/" \
        --include='.env.example' --exclude='.env' --exclude='.env.*' \
        --exclude='vendor/' --exclude='node_modules/' --exclude='tests/' \
        --exclude='storage/logs/*.log' --exclude='storage/framework/cache/data/*' \
        --exclude='storage/framework/sessions/*' --exclude='storage/framework/views/*.php' \
        --exclude='bootstrap/cache/*.php' --exclude='.phpunit.cache/' --exclude='.claude/' \
        --exclude='phpunit*.xml' --exclude='phpstan.neon' --exclude='.git*'

    composer install --working-dir="$STAGE/api" --no-dev --optimize-autoloader --classmap-authoritative \
        --no-interaction --no-progress --prefer-dist --no-scripts

    echo "$VERSION" > "$STAGE/api/RELEASE"
    tar -czf "$DIST/bizzmag-api-$VERSION.tar.gz" -C "$STAGE" api
    echo "    dist/bizzmag-api-$VERSION.tar.gz"
}

build_app() {
    if [[ -z "${NEXT_PUBLIC_API_URL:-}" ]]; then
        echo "NEXT_PUBLIC_API_URL is not set. The web app needs the API address at build time." >&2
        echo "Example: NEXT_PUBLIC_API_URL=https://api.your-domain $0 $VERSION" >&2
        exit 65
    fi

    echo "==> Building web app package $VERSION for API $NEXT_PUBLIC_API_URL"
    (
        cd "$ROOT/frontend"
        pnpm install --frozen-lockfile
        NEXT_PUBLIC_APP_ENV="${NEXT_PUBLIC_APP_ENV:-production}" pnpm build
        pnpm -s check:export
    )

    echo "$VERSION" > "$ROOT/frontend/out/RELEASE"
    tar -czf "$DIST/bizzmag-app-$VERSION.tar.gz" -C "$ROOT/frontend/out" .
    echo "    dist/bizzmag-app-$VERSION.tar.gz"
}

case "$MODE" in
    --api-only) build_api ;;
    --app-only) build_app ;;
    "") build_api; build_app ;;
    *) echo "Unknown option: $MODE" >&2; exit 64 ;;
esac

echo "==> Done. Deploy with infrastructure/deploy-remote.sh or the runbooks in docs/runbooks/."

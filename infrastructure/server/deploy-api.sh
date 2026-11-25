#!/usr/bin/env bash
# ----------------------------------------------------------------------------
# Installs an API release ON THE SERVER (cPanel or VPS). Run over SSH or from
# cPanel > Terminal. Same layout on every host:
#
#   $APP_DIR/
#     releases/<timestamp>/     one folder per release (last 5 kept)
#     shared/.env               your settings, kept across releases
#     shared/storage/           uploads, logs, sessions, kept across releases
#     current -> releases/...   the live release (switched atomically)
#
# Point the API's document root at $APP_DIR/current/public.
#
# Usage:
#   deploy-api.sh install  <bizzmag-api-x.y.z.tar.gz>
#   deploy-api.sh rollback
#
# Environment:
#   APP_DIR   default: $HOME/apps/bizzmag-api
#   PHP_BIN   default: php  (cPanel example: /opt/cpanel/ea-php83/root/usr/bin/php)
#   KEEP      releases to keep, default 5
# ----------------------------------------------------------------------------
set -euo pipefail

ACTION="${1:-}"
APP_DIR="${APP_DIR:-$HOME/apps/bizzmag-api}"
PHP_BIN="${PHP_BIN:-php}"
KEEP="${KEEP:-5}"

log() { printf '==> %s\n' "$*"; }
fail() { printf 'ERROR: %s\n' "$*" >&2; exit 1; }

switch_current() {
    # Atomic on Linux (GNU mv -T). Falls back to ln -sfn elsewhere.
    local target="$1"
    ln -sfn "$target" "$APP_DIR/current.next"
    if mv --version >/dev/null 2>&1; then
        mv -Tf "$APP_DIR/current.next" "$APP_DIR/current"
    else
        rm -f "$APP_DIR/current.next"
        ln -sfn "$target" "$APP_DIR/current"
    fi
}

artisan() { (cd "$1" && "$PHP_BIN" artisan "${@:2}" --no-interaction); }

ensure_shared() {
    mkdir -p "$APP_DIR/releases" \
        "$APP_DIR/shared/storage/app/public" \
        "$APP_DIR/shared/storage/framework/cache/data" \
        "$APP_DIR/shared/storage/framework/sessions" \
        "$APP_DIR/shared/storage/framework/views" \
        "$APP_DIR/shared/storage/logs"
    chmod -R ug+rwX "$APP_DIR/shared/storage"
}

install_release() {
    local tarball="$1"
    [[ -f "$tarball" ]] || fail "Release file not found: $tarball"
    command -v "$PHP_BIN" >/dev/null 2>&1 || fail "PHP not found at '$PHP_BIN'. Set PHP_BIN to your PHP 8.3 binary."

    ensure_shared

    local stamp release
    stamp="$(date -u +%Y%m%d%H%M%S)"
    release="$APP_DIR/releases/$stamp"
    mkdir -p "$release"
    log "Unpacking $(basename "$tarball") into releases/$stamp"
    tar -xzf "$tarball" -C "$release" --strip-components=1

    if [[ ! -f "$APP_DIR/shared/.env" ]]; then
        cp "$release/.env.example" "$APP_DIR/shared/.env"
        chmod 600 "$APP_DIR/shared/.env"
        rm -rf "$release"
        fail "First install: created $APP_DIR/shared/.env from the example. Fill in the database, URLs and mail settings (see docs/runbooks), then run this command again. APP_KEY is generated for you."
    fi

    ln -sfn "$APP_DIR/shared/.env" "$release/.env"
    rm -rf "$release/storage"
    ln -sfn "$APP_DIR/shared/storage" "$release/storage"
    mkdir -p "$release/bootstrap/cache"

    if ! grep -qE '^APP_KEY=base64:' "$APP_DIR/shared/.env"; then
        log "Generating APP_KEY"
        artisan "$release" key:generate --force
    fi

    local version
    version="$(cat "$release/RELEASE" 2>/dev/null || echo "$stamp")"
    if grep -qE '^APP_VERSION=' "$APP_DIR/shared/.env"; then
        sed -i.bak -E "s/^APP_VERSION=.*/APP_VERSION=$version/" "$APP_DIR/shared/.env" && rm -f "$APP_DIR/shared/.env.bak"
    else
        printf '\nAPP_VERSION=%s\n' "$version" >> "$APP_DIR/shared/.env"
    fi

    log "Running database migrations"
    artisan "$release" migrate --force

    log "Caching configuration, routes, events and views"
    artisan "$release" config:cache
    artisan "$release" route:cache
    artisan "$release" event:cache
    artisan "$release" view:cache

    log "Checking the server (app:preflight)"
    if ! artisan "$release" app:preflight; then
        artisan "$release" config:clear >/dev/null || true
        fail "Preflight failed. The live site was NOT switched. Fix the items above and run this command again."
    fi

    log "Switching live release to $stamp"
    switch_current "$release"

    ln -sfn "$APP_DIR/shared/storage/app/public" "$release/public/storage" 2>/dev/null || true
    artisan "$APP_DIR/current" queue:restart || true

    log "Removing old releases (keeping $KEEP)"
    ls -1d "$APP_DIR"/releases/*/ 2>/dev/null | sort \
        | awk -v keep="$KEEP" '{ lines[NR] = $0 } END { for (i = 1; i <= NR - keep; i++) print lines[i] }' \
        | while read -r old; do rm -rf "$old"; done

    log "Deployed release $version. Check: curl -fsS <your API URL>/up"
}

rollback_release() {
    local current previous
    current="$(readlink "$APP_DIR/current" || true)"
    [[ -n "$current" ]] || fail "No current release to roll back from."
    previous="$(ls -1d "$APP_DIR"/releases/*/ | sed 's:/*$::' | sort \
        | awk -v current="$current" '$0 == current { print previous; exit } { previous = $0 }')"
    [[ -n "$previous" && "$previous" != "$current" ]] || fail "No earlier release to roll back to."

    log "Rolling back from $(basename "$current") to $(basename "$previous")"
    switch_current "$previous"
    artisan "$APP_DIR/current" config:cache
    artisan "$APP_DIR/current" queue:restart || true
    log "Rolled back. Database migrations are not reversed; releases are designed to be backward compatible."
}

case "$ACTION" in
    install) install_release "${2:-}" ;;
    rollback) rollback_release ;;
    *) echo "Usage: deploy-api.sh install <bizzmag-api-x.y.z.tar.gz> | rollback" >&2; exit 64 ;;
esac

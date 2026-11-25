#!/usr/bin/env bash
# ----------------------------------------------------------------------------
# Deploys release packages FROM YOUR COMPUTER to a server over SSH.
# Works for cPanel (with SSH access) and VPS. Without SSH, follow the
# File Manager steps in docs/runbooks/deploy-cpanel.md instead.
#
# Usage:
#   SSH_TARGET=user@server.example.com \
#   API_DIR='~/apps/bizzmag-api' APP_DOCROOT='~/app.your-domain' \
#   PHP_BIN=/opt/cpanel/ea-php83/root/usr/bin/php \
#   infrastructure/deploy-remote.sh 1.0.0 [api|app|both]
#
# SSH_PORT defaults to 22. API_DIR and PHP_BIN are optional.
# ----------------------------------------------------------------------------
set -euo pipefail

VERSION="${1:-}"
WHAT="${2:-both}"
: "${SSH_TARGET:?Set SSH_TARGET=user@host}"
SSH_PORT="${SSH_PORT:-22}"
API_DIR="${API_DIR:-~/apps/bizzmag-api}"
PHP_BIN="${PHP_BIN:-php}"

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
SSH=(ssh -p "$SSH_PORT" "$SSH_TARGET")
SCP=(scp -P "$SSH_PORT")

[[ -n "$VERSION" ]] || { echo "Usage: deploy-remote.sh <version> [api|app|both]" >&2; exit 64; }

"${SSH[@]}" "mkdir -p ~/bizzmag-deploy"
"${SCP[@]}" "$ROOT/infrastructure/server/deploy-api.sh" "$ROOT/infrastructure/server/deploy-app.sh" "$SSH_TARGET:~/bizzmag-deploy/"

if [[ "$WHAT" == "api" || "$WHAT" == "both" ]]; then
    "${SCP[@]}" "$ROOT/dist/bizzmag-api-$VERSION.tar.gz" "$SSH_TARGET:~/bizzmag-deploy/"
    "${SSH[@]}" "APP_DIR=$API_DIR PHP_BIN=$PHP_BIN bash ~/bizzmag-deploy/deploy-api.sh install ~/bizzmag-deploy/bizzmag-api-$VERSION.tar.gz"
fi

if [[ "$WHAT" == "app" || "$WHAT" == "both" ]]; then
    : "${APP_DOCROOT:?Set APP_DOCROOT to the web app document root, for example ~/app.your-domain}"
    "${SCP[@]}" "$ROOT/dist/bizzmag-app-$VERSION.tar.gz" "$SSH_TARGET:~/bizzmag-deploy/"
    "${SSH[@]}" "bash ~/bizzmag-deploy/deploy-app.sh ~/bizzmag-deploy/bizzmag-app-$VERSION.tar.gz $APP_DOCROOT"
fi

echo "==> Release $VERSION deployed to $SSH_TARGET"

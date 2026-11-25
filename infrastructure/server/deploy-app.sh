#!/usr/bin/env bash
# ----------------------------------------------------------------------------
# Publishes the static web app ON THE SERVER (cPanel or VPS).
#
# Usage:
#   deploy-app.sh <bizzmag-app-x.y.z.tar.gz> <document root>
#
# Example (cPanel subdomain app.your-domain):
#   deploy-app.sh ~/bizzmag-app-1.0.0.tar.gz ~/app.your-domain
#
# Hashed build files never clash, so new files are copied first and stale
# files removed last. The .well-known folder (used by AutoSSL / Let's
# Encrypt) is never touched.
# ----------------------------------------------------------------------------
set -euo pipefail

TARBALL="${1:-}"
DOCROOT="${2:-}"

[[ -f "$TARBALL" && -n "$DOCROOT" ]] || { echo "Usage: deploy-app.sh <bizzmag-app-x.y.z.tar.gz> <document root>" >&2; exit 64; }

STAGE="$(mktemp -d)"
trap 'rm -rf "$STAGE"' EXIT

tar -xzf "$TARBALL" -C "$STAGE"
[[ -f "$STAGE/index.html" && -f "$STAGE/sw.js" ]] || { echo "ERROR: $TARBALL is not a web app package (index.html or sw.js missing)." >&2; exit 1; }

mkdir -p "$DOCROOT"

if command -v rsync >/dev/null 2>&1; then
    rsync -a --delete-after --exclude='.well-known/' "$STAGE/" "$DOCROOT/"
else
    # No rsync (some shared hosts): copy over, then remove files not in the release.
    cp -R "$STAGE/." "$DOCROOT/"
    (cd "$DOCROOT" && find . -type f ! -path './.well-known/*' | while read -r file; do
        [[ -e "$STAGE/$file" ]] || rm -f "$file"
    done)
fi

echo "==> Web app $(cat "$DOCROOT/RELEASE" 2>/dev/null || echo '') published to $DOCROOT"

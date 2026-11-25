#!/usr/bin/env bash
# ----------------------------------------------------------------------------
# HTTP smoke tests against a deployed stack (CI verification).
#
# Usage: smoke-test.sh <expected release> [base url]
# The web server must route by Host header: api.bizzmag.test, app.bizzmag.test.
# ----------------------------------------------------------------------------
set -uo pipefail

RELEASE="${1:?expected release}"
BASE="${2:-http://127.0.0.1:8080}"
APP_ORIGIN="http://app.bizzmag.test:8080"
FAILURES=0

pass() { printf '  PASS  %s\n' "$1"; }
fail() { printf '  FAIL  %s\n' "$1"; FAILURES=$((FAILURES + 1)); }

api() { curl -s -H "Host: api.bizzmag.test" -H "Accept: application/json" "$@"; }
app() { curl -s -H "Host: app.bizzmag.test" "$@"; }
status() { "$@" -o /dev/null -w '%{http_code}'; }
check() { if [[ "$2" == "$3" ]]; then pass "$1"; else fail "$1 (expected $3, got $2)"; fi; }
contains() { if grep -q -- "$3" <<<"$2"; then pass "$1"; else fail "$1 (missing: $3)"; fi; }

echo "Smoke testing release $RELEASE at $BASE"

check "API health /up returns 200" "$(status api "$BASE/up")" 200

meta="$(api "$BASE/api/v1/meta")"
contains "API meta returns the success envelope" "$meta" '"success":true'
contains "API reports release $RELEASE" "$meta" "\"release\":\"$RELEASE\""

contains "Unknown API route returns the JSON 404 envelope" "$(api "$BASE/api/v1/does-not-exist")" '"code":"NOT_FOUND"'

home="$(app "$BASE/")"
contains "Web app home page is served" "$home" "Choose where to work"
contains "Web app pages carry the Content Security Policy" "$home" 'http-equiv="Content-Security-Policy"'

check "Deep link /desk/ returns 200" "$(status app "$BASE/desk/")" 200
no_slash="$(app -D - -o /dev/null "$BASE/desk" | tr -d '\r')"
if grep -q "^HTTP/[0-9.]* 200" <<<"$no_slash" || grep -qiE "^Location: .*/desk/$" <<<"$no_slash"; then
    pass "Deep link /desk without slash resolves to /desk/"
else
    fail "Deep link /desk without slash resolves to /desk/"
fi
check "Unknown page returns 404" "$(status app "$BASE/no-such-page/")" 404
contains "Unknown page shows the app's not-found page" "$(app "$BASE/no-such-page/")" "Page not found"

sw_headers="$(app -D - -o /dev/null "$BASE/sw.js")"
contains "Service worker is served" "$sw_headers" "200"
contains "Service worker is never cached by the browser" "$(tr '[:upper:]' '[:lower:]' <<<"$sw_headers")" "cache-control: no-cache"
check "Web app manifest is served" "$(status app "$BASE/manifest.webmanifest")" 200

cors="$(api -X OPTIONS -D - -o /dev/null -H "Origin: $APP_ORIGIN" -H "Access-Control-Request-Method: POST" "$BASE/api/v1/meta" | tr -d '\r')"
contains "CORS allows the web app origin" "$cors" "Access-Control-Allow-Origin: $APP_ORIGIN"
contains "CORS allows credentials (cookie login)" "$cors" "Access-Control-Allow-Credentials: true"

foreign="$(api -X OPTIONS -D - -o /dev/null -H "Origin: https://evil.example" -H "Access-Control-Request-Method: POST" "$BASE/api/v1/meta" | tr -d '\r')"
if grep -qi "Access-Control-Allow-Origin: https://evil.example" <<<"$foreign"; then
    fail "CORS must not allow a foreign origin"
else
    pass "CORS does not allow a foreign origin"
fi

csrf="$(api -D - -o /dev/null -H "Origin: $APP_ORIGIN" "$BASE/sanctum/csrf-cookie" | tr -d '\r')"
contains "CSRF cookie is issued" "$csrf" "XSRF-TOKEN="
contains "CSRF cookie is shared across the parent domain" "$(tr '[:upper:]' '[:lower:]' <<<"$csrf")" "domain=.bizzmag.test"

if [[ "$FAILURES" -gt 0 ]]; then
    echo "$FAILURES smoke test(s) failed."
    exit 1
fi

echo "All smoke tests passed."

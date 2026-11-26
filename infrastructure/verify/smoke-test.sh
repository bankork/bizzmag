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
PORT="${BASE##*:}"
HOST_IP="${BASE#*://}"; HOST_IP="${HOST_IP%%:*}"
APP_ORIGIN="http://app.bizzmag.test:$PORT"
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

# ---------------------------------------------------------------------------
# Cookie sign-in exactly as the web app does it (SEC-023): CSRF cookie,
# login with X-XSRF-TOKEN, then /me, then a refused admin request.
# Real host names are resolved to the test server so cookies scoped to
# .bizzmag.test are stored and sent back.
# ---------------------------------------------------------------------------
API="http://api.bizzmag.test:$PORT/api/v1"
JAR="$(mktemp)"
trap 'rm -f "$JAR"' EXIT

session() {
    curl -s --resolve "api.bizzmag.test:$PORT:$HOST_IP" -b "$JAR" -c "$JAR" \
        -H "Accept: application/json" -H "Origin: $APP_ORIGIN" -H "Referer: $APP_ORIGIN/" "$@"
}
xsrf() {
    local raw
    raw="$(awk '$6 == "XSRF-TOKEN" { print $7 }' "$JAR" | tail -n 1)"
    printf '%b' "${raw//%/\\x}"
}
send() { local method="$1" path="$2" body="$3"; session -X "$method" -H "Content-Type: application/json" -H "X-XSRF-TOKEN: $(xsrf)" -d "$body" "$API/$path"; }
sign_in() { session -o /dev/null "http://api.bizzmag.test:$PORT/sanctum/csrf-cookie"; send POST auth/login "{\"identifier\":\"$1\",\"password\":\"$2\"}"; }

login="$(sign_in owner@bizzmag.test Verify-owner-2026)"
contains "Owner signs in with a cookie session" "$login" '"success":true'
contains "Sign-in returns the owner's permissions" "$login" '"user.manage"'
contains "Signed-in /me works with the session cookie" "$(session "$API/me")" '"home":"admin"'

employee_role="$(session "$API/roles" | grep -o '"id":[0-9]*,"name":"Employee"' | grep -o '[0-9]*' | awk 'NR == 1')"
staff_email="smoke-$RANDOM$RANDOM@bizzmag.test"
created="$(send POST users "{\"name\":\"Smoke Employee\",\"email\":\"$staff_email\",\"password\":\"Smoke-temp-2026\",\"role_ids\":[$employee_role]}")"
contains "Owner creates an employee" "$created" '"must_change_password":true'
send POST auth/logout '{}' >/dev/null

sign_in "$staff_email" Smoke-temp-2026 >/dev/null
send PUT me/password '{"current_password":"Smoke-temp-2026","password":"Smoke-new-2026","password_confirmation":"Smoke-new-2026"}' >/dev/null
check "An employee is refused an admin endpoint" "$(session -o /dev/null -w '%{http_code}' "$API/settings")" 403
send POST auth/logout '{}' >/dev/null
check "After signing out, /me needs a sign-in again" "$(session -o /dev/null -w '%{http_code}' "$API/me")" 401

if [[ "$FAILURES" -gt 0 ]]; then
    echo "$FAILURES smoke test(s) failed."
    exit 1
fi

echo "All smoke tests passed."

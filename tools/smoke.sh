#!/usr/bin/env bash
# Smoke test: tools/smoke.sh BASE_URL [COOKIE]
#   BASE_URL  e.g. http://localhost:8080 or https://dolumar.example.com
#   COOKIE    optional Cookie header value, sent with every request
# Env: SMOKE_NO_OAUTH=1 skips the /oauth2/login redirect check (use when no
#      OpenID Connect provider is configured; the engine fatals in that case).
# Requires only bash, curl and grep. Exits non-zero if any check fails.
set -u

BASE="${1:-}"
COOKIE="${2:-}"
if [ -z "$BASE" ]; then
	echo "usage: $0 BASE_URL [COOKIE]" >&2
	exit 2
fi
BASE="${BASE%/}"

BODY="$(mktemp)"
HEADERS="$(mktemp)"
trap 'rm -f "$BODY" "$HEADERS"' EXIT

FAILED=0
SKIPPED=0
BAD_BODY='Deprecated:|Warning:|Notice:|Fatal error|Stack trace'

# check NAME EXPECTED_STATUS PATH [curl args...]
# Sets globals STATUS; leaves body/headers in $BODY/$HEADERS.
# Extra assertions: set EXPECT_HEADER (regex, case-insensitive), EXPECT_BODY,
# FORBID_BODY (regexes) before calling; they are reset afterwards.
EXPECT_HEADER=""
EXPECT_BODY=""
FORBID_BODY=""

check() {
	local name="$1" want="$2" path="$3"
	shift 3
	local cookie_args=()
	[ -n "$COOKIE" ] && cookie_args=(-H "Cookie: $COOKIE")

	STATUS="$(curl -sS --path-as-is -m 30 -o "$BODY" -D "$HEADERS" -w '%{http_code}' \
		"${cookie_args[@]}" "$@" "$BASE$path" 2>&1)" || true

	local problems=""
	[ "$STATUS" = "$want" ] || problems="status $STATUS (want $want)"
	if grep -aqE "$BAD_BODY" "$BODY"; then
		problems="$problems; body contains PHP error output"
	fi
	if [ -n "$EXPECT_HEADER" ] && ! grep -aiqE "$EXPECT_HEADER" "$HEADERS"; then
		problems="$problems; header /$EXPECT_HEADER/ missing"
	fi
	if [ -n "$EXPECT_BODY" ] && ! grep -aqE "$EXPECT_BODY" "$BODY"; then
		problems="$problems; body /$EXPECT_BODY/ missing"
	fi
	if [ -n "$FORBID_BODY" ] && grep -aqE "$FORBID_BODY" "$BODY"; then
		problems="$problems; body matches forbidden /$FORBID_BODY/"
	fi
	EXPECT_HEADER="" EXPECT_BODY="" FORBID_BODY=""

	if [ -z "$problems" ]; then
		printf 'ok   %-45s %s\n' "$name" "$STATUS"
	else
		printf 'FAIL %-45s %s\n' "$name" "${problems#; }"
		FAILED=$((FAILED + 1))
	fi
}

# --- Pages -----------------------------------------------------------------
EXPECT_BODY='CONFIG_GAME_NAME'
check "GET /" 200 /

EXPECT_BODY='<root><runtime><session_id>[0-9a-f]{32}</session_id>'
check "dialog route (login)" 200 /dialog/login/

# --- Map: what the client loads on startup (gamemap.js, minimap.js) ----------
# POST map/region/?tiles=N with regions[i][0|1]=x|y as form body.
EXPECT_HEADER='^content-type: application/json'
EXPECT_BODY='"regions":\['
check "POST map/region (client request)" 200 '/map/region/?tiles=8' \
	-X POST --data 'regions[0][0]=0&regions[0][1]=0&regions[1][0]=1&regions[1][1]=0'

EXPECT_HEADER='^content-type: image/png'
check "GET image/minimap" 200 '/image/minimap/?x=0&y=0'

# --- Login -------------------------------------------------------------------
if [ "${SMOKE_NO_OAUTH:-0}" = "1" ]; then
	printf 'skip %-45s SMOKE_NO_OAUTH=1\n' "GET /oauth2/login"
	SKIPPED=$((SKIPPED + 1))
else
	EXPECT_HEADER='^location: https?://'
	check "GET /oauth2/login redirects to accounts" 302 /oauth2/login
fi

# --- Things that must not be reachable anonymously ----------------------------
check "GET /test/info anonymous" 404 /test/info

FORBID_BODY='Running |Setup|CREATE TABLE|Installing'
check "GET /cronjobs.php executes nothing" 200 /cronjobs.php

FORBID_BODY='Running |Setup|CREATE TABLE|Installing'
check "GET /setup.php executes nothing" 200 /setup.php

FORBID_BODY='root:|daemon:'
check "GET /cache.php traversal (file removed)" 200 '/cache.php?d=/../../etc/passwd'

check "GET /api/clearcache anonymous" 403 /api/clearcache
check "GET /api/getlogs/1 anonymous" 403 /api/getlogs/1

EXPECT_HEADER='^content-type: application/json'
check "GET /api/countx json content-type" 200 '/api/countx/?output=json&a=smoke'

check "GET gameserver asset traversal" 404 '/gameserver/assets/..%2f..%2fcomposer.json'

# Apache's .htaccess rule forbids *.php under signs/ even if the file does not exist.
check "GET /signs/x.php forbidden" 403 /signs/x.php

echo
if [ "$FAILED" -gt 0 ]; then
	echo "$FAILED check(s) failed"
	exit 1
fi
if [ "$SKIPPED" -gt 0 ]; then
	echo "all checks passed ($SKIPPED skipped)"
else
	echo "all checks passed"
fi

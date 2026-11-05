#!/usr/bin/env bash
# Deploys one commit to every Dolumar world (each world is its own Dokku app), one after the other.
#
#   tools/deploy.sh [options] [REF]
#
#   REF            branch, tag or commit to deploy (default: origin/master). A branch name (master, develop,
#                  origin/master...) always means the branch as it is on GitHub now, never a local copy of it.
#   -y, --yes      do not ask for confirmation
#   -f, --force    force-push (needed when the apps run a commit that is not an ancestor of REF, e.g. a rollback)
#   --only APP     deploy to this app only (repeatable)
#   -h, --help     show this help
#
# Settings (environment variables, or put them in tools/deploy.env, which is not committed; copy
# tools/deploy.env.example. That file is sourced by bash, so a value with spaces needs quotes):
#   DOLUMAR_DOKKU_HOST  Dokku server, e.g. dokku@dolumar.example.com. Default: the host of the "dokku" git remote.
#   DOLUMAR_APPS        the apps, in deploy order. Default: "dolumar-speed dolumar-classic dolumar".
#
# Order matters: the first app that fails stops the run, and the apps after it keep their old release. Keep the
# long-running world last, so a bad schema upgrade (tools/setup.php runs in predeploy) shows up on a young world
# first. A Dokku deploy that fails leaves that app on its previous release.
#
# Every app gets the same commit. Per-world differences (speed, NEVERENDING, domain, keys) are Dokku config, never
# code: see README "Running several worlds".
set -euo pipefail

cd "$(dirname "$0")/.."

usage() {
	sed -n '2,/^set -euo/p' "$0" | sed '$d' | sed 's/^# \{0,1\}//'
}

if [ -f tools/deploy.env ]; then
	# shellcheck disable=SC1091
	. tools/deploy.env
fi

ASSUME_YES=0
FORCE=0
ONLY=()
REF=""

while [ $# -gt 0 ]; do
	case "$1" in
		-y|--yes) ASSUME_YES=1 ;;
		-f|--force) FORCE=1 ;;
		--only) shift; ONLY+=("${1:?--only needs an app name}") ;;
		-h|--help) usage; exit 0 ;;
		-*) echo "Unknown option: $1" >&2; usage >&2; exit 2 ;;
		*) [ -z "$REF" ] || { echo "Only one REF, got '$REF' and '$1'" >&2; exit 2; }; REF="$1" ;;
	esac
	shift
done

REF="${REF:-origin/master}"

# --- Dokku host ---------------------------------------------------------------------------------------------------
HOST="${DOLUMAR_DOKKU_HOST:-}"
if [ -z "$HOST" ]; then
	remote_url="$(git remote get-url dokku 2>/dev/null || true)"
	# dokku@host:app or ssh://dokku@host[:port]/app
	case "$remote_url" in
		ssh://*) HOST="${remote_url#ssh://}"; HOST="${HOST%%/*}" ;;
		*:*) HOST="${remote_url%%:*}" ;;
	esac
fi
if [ -z "$HOST" ]; then
	echo "No Dokku host: set DOLUMAR_DOKKU_HOST=dokku@your-server (or add a 'dokku' git remote)." >&2
	exit 2
fi
case "$HOST" in *@*) ;; *) HOST="dokku@$HOST" ;; esac

# --- Apps ---------------------------------------------------------------------------------------------------------
read -r -a APPS <<< "${DOLUMAR_APPS:-dolumar-speed dolumar-classic dolumar}"
if [ ${#ONLY[@]} -gt 0 ]; then
	for app in "${ONLY[@]}"; do
		found=0
		for known in "${APPS[@]}"; do [ "$app" = "$known" ] && found=1; done
		[ $found -eq 1 ] || { echo "Unknown app '$app' (apps: ${APPS[*]})" >&2; exit 2; }
	done
	APPS=("${ONLY[@]}")
fi

# dokku over ssh: the dokku user's forced command takes the dokku subcommand as the ssh command.
dokku_cmd() {
	ssh -o BatchMode=yes "$HOST" "$@"
}

# --- What gets deployed -------------------------------------------------------------------------------------------
# A branch is fetched by an explicit refspec, so a clone with a narrowed fetch config (single-branch clone) or a stale
# local branch of the same name can never put an old commit on the servers.
fetch_branch() {
	git fetch --quiet origin "+refs/heads/$1:refs/remotes/origin/$1"
}

echo "Fetching origin..."
BRANCH=""
case "$REF" in
	origin/*) BRANCH="${REF#origin/}" ;;
	refs/heads/*) BRANCH="${REF#refs/heads/}" ;;
	*) if git ls-remote --exit-code --heads origin "refs/heads/$REF" >/dev/null 2>&1; then BRANCH="$REF"; fi ;;
esac
if [ -n "$BRANCH" ]; then
	if ! fetch_branch "$BRANCH"; then
		echo "Cannot fetch branch '$BRANCH' from origin ($(git remote get-url origin))." >&2
		exit 2
	fi
	SHA="$(git rev-parse --verify --quiet "refs/remotes/origin/$BRANCH^{commit}" || true)"
	if [ "$REF" = "$BRANCH" ] && local_sha="$(git rev-parse --verify --quiet "refs/heads/$BRANCH^{commit}")" \
		&& [ "$local_sha" != "$SHA" ]; then
		echo "Note: your local '$BRANCH' (${local_sha:0:12}) differs from origin; deploying origin/$BRANCH."
	fi
else
	git fetch --quiet --tags origin
	SHA="$(git rev-parse --verify --quiet "$REF^{commit}" || true)"
fi
if [ -z "$SHA" ]; then
	echo "Cannot resolve '$REF' to a commit." >&2
	exit 2
fi

# The servers only have PHP 8.x. A commit from before the PHP 8 port fails in the build with a long Composer error
# (zendframework packages that need PHP 5/7), so refuse it here with a clear message.
if ! git show "$SHA:composer.json" 2>/dev/null | grep -Eq '"php"[[:space:]]*:[[:space:]]*"[^"]*8\.'; then
	echo "Refusing to deploy $(git log -1 --format='%h %s (%cs)' "$SHA"):" >&2
	echo "its composer.json does not require PHP 8, so it predates the PHP 8 port and cannot build on Dokku." >&2
	echo "Is '$REF' the right ref? Check: git log --no-walk --format='%h %cs %s' origin/master origin/develop" >&2
	exit 2
fi

echo
echo "Deploying $(git log -1 --format='%h %s (%an, %ar)' "$SHA")"
echo "Server:    $HOST"
if [ "$BRANCH" != develop ] && fetch_branch develop 2>/dev/null; then
	missing="$(git rev-list --count "$SHA..refs/remotes/origin/develop" 2>/dev/null || echo 0)"
	if [ "$missing" -gt 0 ]; then
		echo "Note:      origin/develop has $missing commit(s) that this commit does not (merge develop first?)"
	fi
fi
echo
printf '  %-24s %-14s %s\n' APP RUNNING URL
for app in "${APPS[@]}"; do
	running="$(dokku_cmd config:get "$app" GIT_REV 2>/dev/null | cut -c1-12 || true)"
	url="$(dokku_cmd config:get "$app" ABSOLUTE_URL 2>/dev/null || true)"
	printf '  %-24s %-14s %s\n' "$app" "${running:-?}" "${url:-?}"
done
echo

if [ $ASSUME_YES -eq 0 ]; then
	read -r -p "Deploy ${SHA:0:12} to ${#APPS[@]} app(s)? [y/N] " answer
	case "$answer" in y|Y|yes|YES) ;; *) echo "Cancelled."; exit 1 ;; esac
fi

# --- Deploy -------------------------------------------------------------------------------------------------------
push_args=()
[ $FORCE -eq 1 ] && push_args+=(--force)

DONE=()
for app in "${APPS[@]}"; do
	echo
	echo "=== $app ==="
	running="$(dokku_cmd config:get "$app" GIT_REV 2>/dev/null || true)"
	if [ "$running" = "$SHA" ]; then
		echo "Already running ${SHA:0:12}, skipped."
		DONE+=("$app (unchanged)")
		continue
	fi

	if ! git push ${push_args[@]+"${push_args[@]}"} "$HOST:$app" "$SHA:refs/heads/master"; then
		echo
		echo "FAILED on $app. It keeps its previous release." >&2
		[ ${#DONE[@]} -gt 0 ] && echo "Already deployed: ${DONE[*]}" >&2
		remaining=0
		for a in "${APPS[@]}"; do
			[ "$a" = "$app" ] && remaining=1 && continue
			[ $remaining -eq 1 ] && echo "Not deployed: $a" >&2
		done
		echo "Logs: ssh $HOST logs $app --num 200" >&2
		exit 1
	fi

	url="$(dokku_cmd config:get "$app" ABSOLUTE_URL 2>/dev/null || true)"
	if [ -n "$url" ]; then
		status="$(curl -s -o /dev/null -w '%{http_code}' -m 30 "$url" || true)"
		echo "$url answers HTTP $status"
		case "$status" in
			2*|3*) ;;
			*) echo "WARNING: $app does not answer normally; check it before going on." >&2
			   if [ $ASSUME_YES -eq 0 ]; then
				   read -r -p "Continue with the next app? [y/N] " answer
				   case "$answer" in y|Y|yes|YES) ;; *) echo "Stopped."; exit 1 ;; esac
			   fi ;;
		esac
	fi
	DONE+=("$app")
done

echo
echo "Done: ${DONE[*]}"

#!/usr/bin/env bash
# Runs after every Dokku deploy (app.json scripts.dokku.postdeploy).
# The schema is installed and upgraded earlier, in tools/predeploy.sh.
set -euo pipefail
cd "$(dirname "$0")/.."

# Preview instances (one per pull request) also get test players. seed.php only acts on a database without players.
if [ "${PREVIEW_MODE:-}" = "1" ]; then
	php tools/seed.php
	# Mark the server as online straight away instead of waiting for the first cron run.
	php cron/daily.php > /dev/null
fi

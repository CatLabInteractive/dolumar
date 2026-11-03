#!/usr/bin/env bash
# Runs on every Dokku deploy (app.json scripts.dokku.predeploy), after the build and before the new containers start.
# Installing or upgrading the schema here means new code never runs against an old schema, and a failing upgrade
# aborts the deploy while the previous release keeps running.
set -euo pipefail
cd "$(dirname "$0")/.."

php tools/setup.php

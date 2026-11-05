#!/usr/bin/env bash
# Runs the game's cron jobs from one long-running container (Procfile `worker`).
#
# cron/run.php runs every minute and cron/ai.php every 15 minutes, each as a fresh PHP process, so memory and
# static state never carry over from one run to the next. Before this worker, Dokku cron started a new container
# for every run, which cost more CPU than the jobs themselves.
#
# The scripts take their own locks (n_locks), so a run that is still busy makes the next one skip. On SIGTERM
# (deploy, restart) no new run starts, and the worker waits for the running ones to finish before it exits.
set -uo pipefail
cd "$(dirname "$0")/.."

RUN_INTERVAL="${WORKER_RUN_INTERVAL:-60}"
AI_INTERVAL="${WORKER_AI_INTERVAL:-900}"

stopping=0
run_pid=0
ai_pid=0

on_term() {
	stopping=1
	echo "worker: stopping, waiting for running jobs"
}
trap on_term TERM INT

is_running() {
	[ "$1" -ne 0 ] && kill -0 "$1" 2> /dev/null
}

last_ai=0

while [ "$stopping" -eq 0 ]; do
	started=$(date +%s)

	# cron/ai.php can take minutes, so it runs next to the minute job and never delays it.
	if [ $((started - last_ai)) -ge "$AI_INTERVAL" ] && ! is_running "$ai_pid"; then
		last_ai=$started
		php cron/ai.php &
		ai_pid=$!
	fi

	if ! is_running "$run_pid"; then
		php cron/run.php &
		run_pid=$!
	fi

	# Wait for the minute job or the end of the interval, whichever comes last. `wait` returns early on a signal.
	while [ "$stopping" -eq 0 ] && is_running "$run_pid"; do
		wait "$run_pid"
	done

	remaining=$((RUN_INTERVAL - ($(date +%s) - started)))
	if [ "$stopping" -eq 0 ] && [ "$remaining" -gt 0 ]; then
		sleep "$remaining" &
		wait $!
	fi
done

for pid in "$run_pid" "$ai_pid"; do
	while is_running "$pid"; do
		wait "$pid"
	done
done
echo "worker: stopped"

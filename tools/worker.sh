#!/usr/bin/env bash
# Runs the game's cron jobs from one long-running container (Procfile `worker`).
#
# cron/run.php starts every minute and cron/ai.php every 15 minutes, each as a fresh PHP process, so memory and
# static state never carry over from one run to the next. Before this worker, Dokku cron started a new container
# for every run, which cost more CPU than the jobs themselves.
#
# A new run starts on every tick even when the previous one is still busy, as with cron: the scripts take their
# own locks (n_locks), so a busy cron/constantly.php is skipped while cron/push.php still runs. A hung process is
# killed by `timeout`, and at most MAX_RUN copies of cron/run.php run at the same time.
#
# On SIGTERM (deploy, restart) no new run starts, and the worker waits for the running jobs before it exits.
set -u
cd "$(dirname "$0")/.." || exit 1

RUN_INTERVAL="${WORKER_RUN_INTERVAL:-60}"
AI_INTERVAL="${WORKER_AI_INTERVAL:-900}"

# constantly.php stops itself after 30 minutes and ai.php has set_time_limit(600); these are the hard limits.
RUN_TIMEOUT=2100
AI_TIMEOUT=900
MAX_RUN=2

stopping=0
sleep_pid=0
ai_pid=0
job_pid=0
run_pids=()

on_term() {
	stopping=1
	echo "worker: stopping, waiting for running jobs"
	if [ "$sleep_pid" -ne 0 ]; then
		kill "$sleep_pid" 2> /dev/null
	fi
}
trap on_term TERM INT

# Starts a job in the background with its output prefixed, and sets job_pid. No command substitution here:
# that would make the job a child of a subshell, and `wait` could not collect it.
start_job() {
	local name=$1 limit=$2
	shift 2
	timeout -k 30 "$limit" "$@" > >(sed -u "s/^/[$name] /") 2>&1 &
	job_pid=$!
}

# Collects a finished job and logs a non-zero exit. Returns 0 when the job is still running.
still_running() {
	local name=$1 pid=$2 status
	if kill -0 "$pid" 2> /dev/null; then
		return 0
	fi
	wait "$pid"
	status=$?
	if [ "$status" -eq 124 ] || [ "$status" -eq 137 ]; then
		echo "worker: $name timed out and was killed"
	elif [ "$status" -ne 0 ]; then
		echo "worker: $name exited with status $status"
	fi
	return 1
}

next_tick=$(date +%s)
next_ai=$next_tick

while [ "$stopping" -eq 0 ]; do
	now=$(date +%s)

	alive=()
	for pid in "${run_pids[@]}"; do
		if still_running run "$pid"; then
			alive+=("$pid")
		fi
	done
	run_pids=("${alive[@]}")

	if [ "${#run_pids[@]}" -lt "$MAX_RUN" ]; then
		start_job run "$RUN_TIMEOUT" php cron/run.php
		run_pids+=("$job_pid")
	else
		echo "worker: $MAX_RUN runs of cron/run.php still busy, skipping this minute"
	fi

	if [ "$ai_pid" -ne 0 ] && ! still_running ai "$ai_pid"; then
		ai_pid=0
	fi
	if [ "$now" -ge "$next_ai" ] && [ "$ai_pid" -eq 0 ]; then
		start_job ai "$AI_TIMEOUT" php cron/ai.php
		ai_pid=$job_pid
		next_ai=$((now + AI_INTERVAL))
	fi

	# Fixed ticks, so a slow loop does not push the schedule later.
	next_tick=$((next_tick + RUN_INTERVAL))
	remaining=$((next_tick - $(date +%s)))
	if [ "$remaining" -le 0 ]; then
		next_tick=$(date +%s)
		continue
	fi
	sleep "$remaining" &
	sleep_pid=$!
	wait "$sleep_pid" 2> /dev/null
	sleep_pid=0
done

for pid in "${run_pids[@]}" "$ai_pid"; do
	if [ "$pid" -ne 0 ]; then
		while kill -0 "$pid" 2> /dev/null; do
			wait "$pid"
		done
	fi
done
echo "worker: stopped"

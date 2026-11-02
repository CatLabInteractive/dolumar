# AGENTS.md

Instructions for AI coding agents working in this repository. Keep this file short: it is the entry point,
and the detailed documentation lives in [`.ai/`](.ai/README.md).

## What this is

Dolumar is a browser-based strategy game (PBBG) written in PHP, first released in 2008/2009. This repository holds the
game itself (`Dolumar_*` classes, templates, language files, cron jobs). It runs on top of
[`dolumar-engine`](https://github.com/CatLabInteractive/dolumar-engine), the "Neuron" game-server framework, which is
installed through Composer. The marketing site lives in a third repository, `dolumar-portal`, which shares no code.

The game is live with real player data. It runs on PHP 8.4 (minimum 8.4.27), MySQL 8 and Memcached, deployed on Dokku.

## Read before working

All AI-facing documentation is in `.ai/`. Start with [`.ai/README.md`](.ai/README.md), then read what applies:

- [`.ai/architecture.md`](.ai/architecture.md): request flow, how game and engine fit together, where things live.
- [`.ai/development.md`](.ai/development.md): local Docker setup, tests, linting, smoke test.
- [`.ai/conventions.md`](.ai/conventions.md): code style, naming, database access, templates and translations.
- [`.ai/deployment.md`](.ai/deployment.md): Dokku, environment variables, cron, persistent storage.
- [`.ai/repositories.md`](.ai/repositories.md): the three Dolumar repositories and how to change the engine.
- [`.ai/superpowers/`](.ai/superpowers/): design specs and implementation plans from earlier work.

## Ground rules

- Work on a feature branch and open a pull request. Never commit directly to `master`.
- Do not change gameplay, balance or game rules unless explicitly asked. A library swap or refactor must not change
  behaviour (types returned from the DB layer, rounding, dates, timezones).
- Production data is the only copy of a live player's kingdom. Never run destructive SQL, and never commit database
  dumps of production data or secrets. Configuration comes from environment variables only.
- Nothing under `public/` may expose setup, cron or maintenance actions to anonymous visitors. CLI scripts start with a
  `PHP_SAPI !== 'cli'` guard.
- The engine is a separate repository. Code under `vendor/` is never edited here; see
  [`.ai/repositories.md`](.ai/repositories.md) for how to change it.
- `pear/` is vendored legacy PEAR code. Leave it alone unless a task is specifically about it.
- New design specs and plans go in `.ai/superpowers/specs/` and `.ai/superpowers/plans/`, not in `docs/`.

## Quick commands

```
docker compose up -d --build
docker compose exec web composer install
docker compose exec web vendor/bin/phpcs                 # PHP 8.4 compatibility check
docker compose exec web vendor/bin/phpunit               # game tests (pure logic, no DB)
docker compose exec -w /engine web vendor/bin/phpunit    # engine tests
tools/smoke.sh http://localhost:8080                     # HTTP smoke test
```

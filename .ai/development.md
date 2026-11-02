# Development

## Local stack

Requires Docker. The engine must be checked out next to this repository as `../dolumar-engine` (or set
`ENGINE_PATH`). `docker-compose.yml` mounts it twice: at `/engine` (to run its tests) and over
`/app/vendor/catlabinteractive/dolumar-engine`, so engine edits are live in the game without a Composer update.

```
cp .env.docker.example .env.docker
docker compose up -d --build
docker compose exec web composer install
docker compose exec web php tools/setup.php     # installs dump/dump.sql into an empty database
mkdir -p public/signs && chmod 777 public/signs
```

- Game: http://localhost:8080
- Mailpit (caught outgoing mail): http://localhost:8025
- Services: `web` (php:8.4-apache, built from `docker/Dockerfile`), `mysql` (8.0), `memcached`, `mailpit`.

Login goes through OpenID Connect (`OPENID_CONNECT_*`). Without a configured provider the home page works but you
cannot log in.

## Checks to run before opening a PR

| Check | Command | Notes |
|-------|---------|-------|
| PHP 8.4 compatibility | `docker compose exec web vendor/bin/phpcs` | Uses `phpcs.xml.dist` (PHPCompatibility, `testVersion 8.4-`). |
| Syntax | `docker compose exec web find src bootstrap cron tools public -name '*.php' -print0 \| xargs -0 -n1 php -l` | Quick and catches most breakage. |
| Game unit tests | `docker compose exec web vendor/bin/phpunit` | `tests/` (PHPUnit 11, `phpunit.xml.dist`). No database or Memcached needed: they cover the pure NPC decision classes in `src/Dolumar/Npc/`. |
| Engine tests | `docker compose exec -w /engine web vendor/bin/phpunit` | Only if the engine was touched. Needs the engine's own `composer install` in `/engine`. |
| Smoke test | `tools/smoke.sh http://localhost:8080` | Add `SMOKE_NO_OAUTH=1` when OIDC is not configured. Fails on any PHP warning, notice or deprecation in the output. |

GitHub Actions (`.github/workflows/tests.yml`) runs the syntax check, phpcs and the game unit tests on every pull
request and on pushes to `master`, on PHP 8.4. Only logic that is pure PHP over arrays can be unit-tested here;
code that talks to the database (windows, village managers, the NPC executor) is verified by playing the affected
flow locally. Say in the PR what you checked.

## Debugging

- Set `APP_DEBUG=1` in `.env.docker` to show errors in the page.
- PHP warnings, notices and deprecations count as bugs: they leak into AJAX responses and break the client.
- `Neuron_Profiler_Profiler` and `Neuron_DB_Database::getAllQueries()` help when tracing slow requests.
- After adding or renaming a class: `docker compose exec web composer dump-autoload`.

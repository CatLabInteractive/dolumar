# Development

## Local stack

Requires Docker. The engine must be checked out next to this repository as `../dolumar-engine` (or set
`ENGINE_PATH`). `docker-compose.yml` mounts it twice: at `/engine` (to run its tests) and over
`/app/vendor/catlabinteractive/dolumar-engine`, so engine edits are live in the game without a Composer update.

```
cp .env.docker.example .env.docker
docker compose up -d --build
docker compose exec web composer install
docker compose exec web php tools/setup.php     # installs dump/dump.sql into an empty database, else runs dump/upgrades/
mkdir -p public/signs && chmod 777 public/signs
```

- Game: http://localhost:8080
- Mailpit (caught outgoing mail): http://localhost:8025
- Services: `web` (php:8.4-apache, built from `docker/Dockerfile`), `mysql` (8.0), `memcached`, `mailpit`.

Login goes through OpenID Connect (`OPENID_CONNECT_*`). `.env.docker.example` points it at `oidc-mock`, a dev-only
provider (`docker/oidc-mock/index.php`, service `oidc-mock`, http://localhost:8090 from the browser and
`http://oidc-mock:8090` from `web`). It accepts everyone: click login on http://localhost:8080 and you come back as
`Mock`. Add `?user=2` to the `/authorize` URL (for example http://localhost:8090/authorize?user=2&redirect_uri=x) to
be remembered as a second player (`Mock2`), so several kingdoms can be created; `?user=1` switches back. The mock
refuses to start without `OIDC_MOCK=1` and must never be deployed. `OPENID_CONNECT_PROFILE_URL_PREFIX` must be non-empty
(an empty value leaves the constant undefined and login dies with a fatal error), and a fresh database needs one
`docker compose exec web php cron/daily.php` run, otherwise login fails with `server_not_online`. Without any provider the home page works but you
cannot log in.

To play locally without a provider, add `PREVIEW_MODE=1` to `.env.docker`, run
`docker compose exec web php tools/seed.php` on an empty database (12 test players with villages, a clan and NPC
tribes) and log in at http://localhost:8080/preview-login.php. Per-PR preview instances use the same mechanism (README,
"Pull request previews").

## Checks to run before opening a PR

| Check | Command | Notes |
|-------|---------|-------|
| PHP 8.4 compatibility | `docker compose exec web vendor/bin/phpcs` | Uses `phpcs.xml.dist` (PHPCompatibility, `testVersion 8.4-`). |
| Syntax | `docker compose exec web find src bootstrap cron tools public -name '*.php' -print0 \| xargs -0 -n1 php -l` | Quick and catches most breakage. |
| Game tests | `docker compose exec web vendor/bin/phpunit` | `tests/` (PHPUnit 11, `phpunit.xml`). Suite `unit` (`tests/Npc`, `tests/Map`, `tests/NewMap`) needs no database (the map tests use `tests/NewMap/FakeDatabase.php`) and is what CI runs; suite `integration` (`tests/Integration`) runs the full game against the Docker database. `--testsuite unit` or `integration` picks one. |
| Engine tests | `docker compose exec -w /engine web vendor/bin/phpunit` | Only if the engine was touched. Needs the engine's own `composer install` in `/engine`. |
| Smoke test | `tools/smoke.sh http://localhost:8080` | Add `SMOKE_NO_OAUTH=1` when OIDC is not configured and `SMOKE_PUSH=1` when the `VAPID_*` keys are set. Fails on any PHP warning, notice or deprecation in the output. |
| Mobile smoke setup | `npm install --no-save playwright && npx playwright install chromium` | Once per machine. |
| Mobile smoke | `node tools/mobile-smoke.js http://localhost:8080 [--only=mobile-shell]` | Browser checks of the desktop and mobile UI. Needs the dev OIDC mock. |

The mobile smoke test needs Node 18+ and Chromium on the host. Nothing is committed under `node_modules/`;
`--no-save` keeps `package.json` out of the repository. Set `MOBILE_SMOKE_OIDC_URL` (default
`http://localhost:8090`) if the OIDC mock runs elsewhere; it is only used for players other than 1.

GitHub Actions (`.github/workflows/tests.yml`) runs the syntax check, phpcs and the `unit` suite on every pull
request and on pushes to `master`, on PHP 8.4. The `integration` suite needs the Docker database and runs locally
only. For anything the tests do not cover (windows, village managers, the NPC executor), verify by playing the
affected flow locally and say in the PR what you checked.

## Schema changes

Add `dump/upgrades/YYYYMMDD-name.sql` and make the same change in `dump/dump.sql` in the same commit. Every file
in that directory runs on every deploy (`tools/setup.php`), so it must be safe to run again (`CREATE TABLE IF NOT
EXISTS`, guarded `ALTER`s). See [`dump/upgrades/README.md`](../dump/upgrades/README.md).

## Debugging

- Set `APP_DEBUG=1` in `.env.docker` to show errors in the page.
- PHP warnings, notices and deprecations count as bugs: they leak into AJAX responses and break the client.
- `Neuron_Profiler_Profiler` and `Neuron_DB_Database::getAllQueries()` help when tracing slow requests.
- After adding or renaming a class: `docker compose exec web composer dump-autoload`.

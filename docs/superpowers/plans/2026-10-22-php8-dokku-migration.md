# Dolumar PHP 8 + Dokku Migration Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Dolumar runs on patched PHP 8.4 in Dokku on a new dedicated droplet, with the production MySQL data moved into Dokku intact and the obvious security holes closed.

**Architecture:** Two repos change. `dolumar-engine` (GitHub, public) gets a `php8` branch that becomes tag `v2.0.0`. `dolumar` (this repo, branch `php8-dokku-migration`) requires `^2.0` and gains the Dokku config. Everything is verified locally first, in a Docker Compose stack (PHP 8.4 + Apache, MySQL 8, memcached, mailpit) loaded with a copy of the production database. Only then does anything run on the new droplet.

**Tech Stack:** PHP 8.4 (Heroku PHP buildpack on Dokku), mysqli, ext-memcached, PHPMailer 6, league/oauth2-client 2.x, vlucas/phpdotenv 5, PHPUnit 11, PHP_CodeSniffer + PHPCompatibility, gitleaks, Dokku + dokku-mysql + dokku-memcached + dokku-letsencrypt.

**Spec:** `docs/superpowers/specs/2026-10-22-php8-dokku-migration-design.md`

## Global Constraints

- PHP requirement in both composer.json files: `"php": "^8.4.27"` (patched for CVE-2026-109618/109619).
- No gameplay, balance or UI changes. Any behaviour change must be a security fix that this plan names.
- The production DB copy and any files from the droplet never get committed. They live in `~/Workbench/dolumar-data/` (outside both repos).
- **Before every SSH session, ask the owner first**, naming the server (old droplet / new droplet) and the commands to run, then wait until they say it's on.
- Branches + PRs only, never commit to master. Run `git branch --show-current` right before every commit.
- Commit messages end with `Co-Authored-By: Claude Opus 5.5 <noreply@anthropic.com>`. PR bodies end with `🤖 Generated with [Claude Code](https://claude.com/claude-code)`.
- `CREDITS_*` and `SERVERLIST_*` unset must work without errors (both services are dead).
- Memcached is required (the map depends on it).
- Composer VCS repositories use `https://github.com/...` URLs, never `git@`.

## Review Focus

1. **Text with non-ASCII characters (é, ü, emoji in player/village names, messages)** must look exactly the same after dump → import → PHP 8 mysqli read. Pinned by Task 1 step 4 and Task 15 step 2.
2. **Failed SQL queries** must behave as they did on PHP 7. PHP 8.1+ mysqli throws exceptions by default, which turns soft failures into fatal errors. Pinned by Task 4's `test_failed_query_returns_false_not_exception`.
3. **Logging in as the existing player** must map to the same `user_id` through `n_auth_openid.openid_url`. A different profile URL prefix creates a new, empty account. Pinned by Task 7's `test_existing_openid_url_maps_to_existing_user`.
4. **The map with memcached down or restarted** should get slower, not crash. Pinned by Task 5's `test_unreachable_server_degrades_to_no_cache`.
5. **Deprecation warnings or notices leaking into HTML or JSON output** must not happen, because they break the JS client. `display_errors` stays off in production, and the smoke test fails on any `Deprecated:`, `Warning:`, `Notice:` or `Fatal error` in a response body. Pinned by Task 15.

---

## Phase 0: Production snapshot

### Task 0: Snapshot the old droplet (read-only)

**Files:** none in repos. Output goes to `~/Workbench/dolumar-data/2026-10-snapshot/`.

- [ ] **Step 1: Ask the owner** to power on the old droplet and give the SSH host/user. Say exactly what will run: mysqldump, read crontab/vhost/php config, tar of the app dir minus vendor. Nothing gets written on the server except the dump file in `/tmp`, which is deleted afterwards. Wait for the go-ahead.
- [ ] **Step 2: Record facts** into `snapshot/FACTS.md`:
  - `php -v`, `php -m`
  - `mysql --version`
  - `crontab -l` for root and the web user
  - the Apache/nginx vhost for game.dolumar.com
  - the app path, with `git -C <app> log -1` and `git -C <app> status`
  - the engine version from `vendor/composer/installed.json`
  - whether `bootstrap/serverconfig.php` or `.env` exist (copy their values; they are secrets, so they stay in `dolumar-data/` only)
  - `du -sh` of `public/signs` and of any other non-git directories under the app
  - whether anything else is served from the droplet (list all vhosts)
- [ ] **Step 3: Charset check:** `SHOW CREATE DATABASE` plus `SELECT TABLE_NAME, TABLE_COLLATION FROM information_schema.TABLES WHERE TABLE_SCHEMA=<db>`. Record the charset (expect latin1 or utf8).
- [ ] **Step 4: Dump** with `mysqldump --single-transaction --routines --triggers --default-character-set=<charset from step 3> --hex-blob <db> | gzip > /tmp/dolumar.sql.gz`, then copy it to `snapshot/` and delete the remote file. Also record `SELECT COUNT(*)` for each table into `snapshot/rowcounts.txt`.
- [ ] **Step 5: Copy files:** tar of `public/signs` and any other non-git user files from step 2.
- [ ] **Step 6: Diff production code** against `origin/master` of this repo and against the engine tag it runs. **If there are uncommitted or unpushed changes, or if login is not the OIDC flow (`oauth2/login`), stop and report to the owner before continuing.**
- [ ] **Step 7:** tell the owner the droplet can be powered off again.

## Phase 1: Local environment

### Task 1: Docker Compose dev stack with the production copy

**Files:**
- Create: `docker/Dockerfile` (must NOT be at the repo root, or Dokku builds from it instead of the buildpack; FROM `php:8.4-apache`; extensions mysqli, gd, bcmath, mbstring, memcached via pecl, zip; `a2enmod rewrite`; DocumentRoot `/app/public`; `AllowOverride All`)
- Create: `docker-compose.yml` (services `web` on :8080, `mysql:8.0` with `--character-set-server` = the snapshot charset, `memcached:1.6`, `axllent/mailpit` on :8025; `.:/app` mount)
- Create: `.env.docker.example`, with `DATABASE_URL=mysql://dolumar:dolumar@mysql/dolumar`, `MEMCACHED_URL=memcached://memcached:11211`, `EMAIL_SMTP_SERVER=mailpit`, `EMAIL_SMTP_PORT=1025`, `PROTOCOL=http`, `ABSOLUTE_URL=http://localhost:8080/`, and empty `OPENID_CONNECT_*`
- Modify: `.gitignore` (add `.env`, `.env.docker`)

- [ ] **Step 1:** stash the owner's unknown composer changes: `git stash push -m "pre-php8 composer pin (owner unsure of origin)" composer.json composer.lock`. Check `git status` is clean.
- [ ] **Step 2:** write the three files and run `docker compose up -d --build`. Expected: `docker compose exec web php -v` prints `PHP 8.4.`, and `php -m` lists memcached, mysqli, gd, bcmath, mbstring.
- [ ] **Step 3:** `gunzip -c ~/Workbench/dolumar-data/2026-10-snapshot/dolumar.sql.gz | docker compose exec -T mysql mysql --default-character-set=<charset> -uroot -p... dolumar`
- [ ] **Step 4: Verify the import.** Row counts match `rowcounts.txt` exactly. Then compare 5 rows that contain non-ASCII text between the dump file (raw bytes) and `SELECT HEX(col)` in the local DB; the bytes must be identical.
- [ ] **Step 5: Commit:** `git add docker docker-compose.yml .env.docker.example .gitignore && git commit -m "Add local PHP 8.4 docker stack"`

## Phase 2: Engine (`dolumar-engine`, branch `php8`)

Clone to `~/Workbench/dolumar-engine` and create branch `php8` from `master`, which includes the session fix `64a98c1`. For local integration, the game's `docker-compose.yml` mounts that clone over `/app/vendor/catlabinteractive/dolumar-engine`.

### Task 2: Engine dependencies and test tooling

**Files:**
- Modify: `composer.json`
- Create: `phpunit.xml.dist`, `tests/bootstrap.php`, `phpcs.xml.dist`

**Interfaces:**
- Produces: `vendor/bin/phpunit` and `vendor/bin/phpcs` working in the engine. `tests/bootstrap.php` defines the DB constants from `DATABASE_URL` (defaulting to the docker MySQL), so the integration tests in Tasks 4/7 can connect.

- [ ] **Step 1:** composer.json changes:
  - `require`: `"php": "^8.4.27"`, `phpmailer/phpmailer ^6.9`, `league/oauth2-client ^2.7`, keep `ext-memcached/mbstring/gd/bcmath`, add `ext-mysqli`
  - remove `silverorange/mdb2`, `bondas83/mdb2_driver_mysqli` (unused: no `MDB2` reference in `src/`) and `ivan-novakov/php-openid-connect-client`
  - keep `openid/php-openid` for now, with its repository URL changed to `https://github.com/CatLabInteractive/php-openid.git`
  - `require-dev`: `phpunit/phpunit ^11`, `squizlabs/php_codesniffer ^3.10`, `phpcompatibility/php-compatibility ^10@dev`
- [ ] **Step 2:** `phpcs.xml.dist` uses the PHPCompatibility standard with `testVersion` `8.4-`, on `src/`.
- [ ] **Step 3:** `composer update`. Expected: it resolves, and the lock contains no `zendframework/*`, `laminas/*` or `pear/*`.
- [ ] **Step 4:** run `vendor/bin/phpcs` and save the error list to the PR description as the baseline (it is expected to fail now).
- [ ] **Step 5: Commit** "Require PHP 8.4, drop MDB2 and Zend-based OIDC client".

### Task 3: Remove PHP-8-incompatible constructs from the engine

**Files:** whatever `phpcs` flags under `src/`. Known: 4 files using `mysql_*`, 2 `create_function`, 1 `ereg`, 2 `get_magic_quotes_*`, plus `each()`, `split()`, curly-brace string offsets, `${}` interpolation, required-after-optional parameters, and `real` casts.

- [ ] **Step 1:** fix each flagged line with its direct PHP 8 equivalent, without changing behaviour:
  - `create_function` → closure
  - `ereg` → `preg_match` with delimiters
  - `split` → `explode` or `preg_split` (match regex vs literal)
  - `each` → `foreach`
  - `get_magic_quotes_gpc()` → `false`
  - `mysql_*` in `OAuth/` → delete the file if nothing references it, otherwise mysqli
- [ ] **Step 2:** `vendor/bin/phpcs` → 0 errors. Then run `find src -name '*.php' -exec php -l {} \;` and confirm nothing reports `Errors parsing`.
- [ ] **Step 3: Commit** "Fix PHP 8 incompatibilities".

### Task 4: mysqli layer keeps PHP 7 semantics

**Files:**
- Modify: `src/Neuron/DB/MySQL.php` (`connect()`, `query()`)
- Test: `tests/DB/MySQLTest.php`

**Interfaces:**
- Consumes: the DB constants from `tests/bootstrap.php`.
- Produces: unchanged public API: `query(string): Neuron_DB_Result|int|bool`, `escape(string): string`, `fromUnixtime(int): string`, `toUnixtime(string): int`, `multiQuery(string)`.

- [ ] **Step 1: Write failing tests:**
  - `test_failed_query_returns_false_not_exception`: `query('SELECT * FROM table_that_does_not_exist')` behaves as on PHP 7 (read the current `query()` body for the exact return value or error path, and assert that), and no `mysqli_sql_exception` escapes.
  - `test_escape_quotes`: `escape("O'Brien")` === `"O\\'Brien"`
  - `test_utf8_roundtrip`: insert and select `'Ünderwörld ⚔'` in a temp table, then assert they're equal.
  - `test_unixtime_roundtrip`: `toUnixtime(fromUnixtime(1700000000)) === 1700000000`
- [ ] **Step 2:** run `vendor/bin/phpunit tests/DB`. Expected: the exception test FAILS with `mysqli_sql_exception`.
- [ ] **Step 3:** in `connect()`, call `mysqli_report(MYSQLI_REPORT_OFF)` before connecting, and `set_charset(<snapshot charset, mapped to utf8mb4 if the source was utf8>)`. The charset comes from an optional `DB_CHARSET` constant, defaulting to `utf8mb4`. Replace the `printf` on connect failure with `throw new RuntimeException('Database connection failed')`, so that credentials and host names never reach the page.
- [ ] **Step 4:** `vendor/bin/phpunit tests/DB` → PASS.
- [ ] **Step 5: Commit** "Keep PHP 7 mysqli error semantics on PHP 8".

### Task 5: Memcached config from Dokku

**Files:**
- Modify: `src/Neuron/Core/Memcache.php`
- Test: `tests/Core/MemcacheTest.php`

**Interfaces:**
- Produces: `public static function parseServers(string $servers): array` on `Neuron_Core_Memcache`. It returns `list<array{0:string,1:int}>` and accepts `host`, `host:port`, `memcached://host:port`, and comma-separated combinations of those.

- [ ] **Step 1: Write failing tests:**
  - `test_parse_plain_host`: `'localhost'` → `[['localhost', 11211]]`
  - `test_parse_url`: `'memcached://dokku-memcached-dolumar:11211'` → `[['dokku-memcached-dolumar', 11211]]`
  - `test_parse_list`: `'a:1,b'` → `[['a',1],['b',11211]]`
  - `test_unreachable_server_degrades_to_no_cache`: with `MEMCACHE_SERVERS` set to `127.0.0.1:1`, `getCache('x')` returns false and `setCache('x', 1)` throws nothing.
- [ ] **Step 2:** run the tests. Expected: FAIL (method missing).
- [ ] **Step 3:** implement `parseServers`. The constructor uses it. Call `setSaslAuthData` only when both username and password are non-empty strings.
- [ ] **Step 4:** run the tests → PASS.
- [ ] **Step 5: Commit** "Accept Dokku MEMCACHED_URL format".

### Task 6: PHPMailer 6

**Files:**
- Modify: `src/connect.php` (around line 299)
- Test: `tests/MailTest.php` (integration, uses mailpit)

- [ ] **Step 1: Failing test:** `test_send_mail_reaches_smtp` calls the existing mail helper in `connect.php` with SMTP pointed at mailpit, then asserts that `GET http://mailpit:8025/api/v1/messages` contains the subject.
- [ ] **Step 2:** run the test → FAIL (class `PHPMailer` not found).
- [ ] **Step 3:** switch to `PHPMailer\PHPMailer\PHPMailer`. Same settings (host, port, secure, auth, from). Catch `PHPMailer\PHPMailer\Exception` and return false, as before.
- [ ] **Step 4:** run the test → PASS.
- [ ] **Step 5: Commit** "Upgrade to PHPMailer 6".

### Task 7: OIDC login on league/oauth2-client, with state check

**Files:**
- Modify: `src/Neuron/GameServer/scripts/openid2/bootstrap.php`, `redirect.php`, `login.php`
- Create: `src/Neuron/Auth/OpenIDConnect.php`
- Test: `tests/Auth/OpenIDConnectTest.php`

**Interfaces:**
- Produces the class `Neuron_Auth_OpenIDConnect`:
  - `__construct(\League\OAuth2\Client\Provider\AbstractProvider $provider)`
  - `static fromConfig(string $redirectUri): self`, which builds a `GenericProvider` from `OPENID_CONNECT_CLIENT_ID/SECRET/AUTHORIZE_URL/TOKEN_URL/PROFILE_URL`
  - `getAuthorizationUrl(): string`, which also stores the state in `$_SESSION['oidc_state']`
  - `getProfile(array $query): array{id:string,email:string,username:string,verified_email:bool}`, which throws `RuntimeException` on a state mismatch or a missing code
  - `static openIdUrlFor(string $id): string`, which returns `OPENID_CONNECT_PROFILE_URL_PREFIX . $id`

- [ ] **Step 1: Failing tests:**
  - `test_state_mismatch_throws`
  - `test_profile_maps_fields`, using a mock provider whose resource owner returns `{id:"42",email:"a@b.c",username:"p",verified_email:true}`
  - `test_existing_openid_url_maps_to_existing_user`: insert `n_auth_openid (openid_url='<prefix>42', user_id=7)`, run the lookup currently inline in `login.php` (extract it to `Neuron_Auth_OpenIDConnect::findUserId(string $openIdUrl): ?int`), then assert the result is 7.
- [ ] **Step 2:** run → FAIL.
- [ ] **Step 3:** implement. The scope stays `openid email profile`. Keep the email-based account linking exactly as in the current `login.php`. After a successful login, `session_regenerate_id(true)` before `handleAuthentication`.
- [ ] **Step 4:** run → PASS.
- [ ] **Step 5: Legacy OpenID 2.0.** Grep for every caller of `Neuron_Auth_OpenID` methods other than `handleAuthentication`, and of `BBGS_Client`. If no route reaches them, delete those methods, the `BBGS/openid.php` + `BBGS/Client.php` OpenID code and `Auth/MySQLConnection.php`, and remove `openid/php-openid` from composer.json. If something still reaches them, keep the library and note why in the PR.
- [ ] **Step 6:** `composer update`, rerun the full test suite, then **commit** "Replace abandoned OIDC client, verify state, regenerate session on login".

### Task 8: Close the open `/test/*` include and the session holes

**Files:**
- Modify: `src/Neuron/GameServer.php:366-405` (the `test` route) and the session start in `openid2/bootstrap.php`
- Test: `tests/GameServerTestRouteTest.php`

**Interfaces:**
- Produces: `Neuron_GameServer::resolveTestScript(?string $name): ?string`, which returns an absolute path inside `SCRIPT_PATH.'tests/'`, or null.

- [ ] **Step 1: Failing tests:**
  - `resolveTestScript('../../../../etc/passwd')` → null
  - `resolveTestScript('giveresources')` → path ending in `tests/giveresources.php`
  - `resolveTestScript('nope')` → null
- [ ] **Step 2:** run → FAIL.
- [ ] **Step 3:** implement:
  - the name must match `/^[a-z0-9_]+$/` and the file must exist
  - delete `$overwritelogin = true` and the relative `@include`
  - the route requires `$player && $player->isAdmin()`, otherwise HTTP 404
- [ ] **Step 4:** session hardening wherever `session_start` is called: `use_strict_mode=1`, `cookie_httponly=1`, `cookie_samesite=Lax`, and `cookie_secure` when the request is HTTPS (`HTTP_X_FORWARDED_PROTO` or `HTTPS`). Read `session_id($_COOKIE['dolumar-auth'])` only under strict mode.
- [ ] **Step 5:** run → PASS. Then **commit** "Lock down test route and session cookies".

### Task 9: Engine PR

- [ ] Push `php8`, open a PR on GitHub `CatLabInteractive/dolumar-engine`. The description lists Tasks 2–8 and the phpcs baseline. The owner merges it and tags `v2.0.0` (or tells Claude to tag it).

## Phase 3: Game repo (`dolumar`, branch `php8-dokku-migration`)

### Task 10: Game dependencies and bootstrap

**Files:**
- Modify: `composer.json`, `bootstrap/bootstrap.php`
- Create: `phpcs.xml.dist` (same as the engine's, on `src bootstrap public cron tools`)

- [ ] **Step 1:** composer.json changes:
  - `require`: `"php": "^8.4.27"`, `catlabinteractive/dolumar-engine` `^2.0` (use `dev-php8` until the tag exists), `vlucas/phpdotenv ^5.6`, `airbrake/phpbrake ^0.8` (or latest), `ext-memcached`, `ext-mysqli`, `ext-gd`, `ext-bcmath`, `ext-mbstring`
  - drop `openid/php-openid` if Task 7 removed it
  - delete the top-level `platform` block and the `secure-http: false` config
  - repositories use `https://github.com/CatLabInteractive/dolumar-engine.git` (and php-openid only if still needed)
  - `require-dev`: `phpunit ^11`, `phpcs`, `phpcompatibility`; drop `zircote/swagger-php`
- [ ] **Step 2:** `bootstrap.php`:
  - `Dotenv\Dotenv::createImmutable(__DIR__.'/..')->safeLoad()`
  - `display_errors` = `getenv('APP_DEBUG') === '1' ? '1' : '0'`, with `log_errors=1`
  - update the Airbrake init to the new phpbrake API
- [ ] **Step 3:** `composer update` resolves. Then run `docker compose exec web php -r "require 'bootstrap/bootstrap.php'; echo 'ok';"` and check it prints `ok`.
- [ ] **Step 4:** run phpcs, fix every flagged construct in `src/ bootstrap/ public/ cron/` (6 known `split()` calls, plus whatever else shows up), then check phpcs reports 0 errors and `php -l` on all files is clean.
- [ ] **Step 5: Commit** "Require PHP 8.4, phpdotenv 5, engine 2.0".

### Task 11: Env config for Dokku and dead services

**Files:**
- Modify: `bootstrap/serverconfig-default.php`, `.env.example`

- [ ] **Step 1:** env mapping:
  - `MEMCACHE_SERVERS` falls back to `MEMCACHED_URL` (dokku-memcached), then `MEMCACHIER_SERVERS`
  - `CACHE_DIR` comes from env `CACHE_DIR`, defaulting to `/tmp/dolumar`
  - `DB_CHARSET` comes from env, defaulting to `utf8mb4`
  - `EMAIL_FROM` comes from env `EMAIL_FROM` (the Mailgun fallback goes, since `MAILGUN_DOMAIN` would make it `dolumar@`)
- [ ] **Step 2:** with all `CREDITS_*` and `SERVERLIST_*` unset, load `/` in docker and the page that offers premium features. Expected: no warnings and no outgoing HTTP calls to credits or serverlist (check with `grep` in the web container's access/error logs and in mailpit). Wrap the call sites so they skip when unconfigured.
- [ ] **Step 3:** update `.env.example` with every variable used, including the new ones. **Commit** "Configure from Dokku env, tolerate missing credits/serverlist".

### Task 12: Cron and setup off the web

**Files:**
- Move: `public/cronjobs.php` → `cron/run.php`; `public/setup.php` → `tools/setup.php`
- Modify: `public/index.php` (remove the `require_once 'cronjobs.php'` "poor man's cron"), `tools/setup.sh`
- Create: `app.json` (replace the Heroku one)

- [ ] **Step 1:** add a guard at the top of `cron/run.php` and `tools/setup.php`: `if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }`. Fix their relative paths to `__DIR__`-based ones.
- [ ] **Step 2:** `app.json`:
  - `scripts.dokku.postdeploy`: `php tools/setup.php`
  - `cron`, with frequencies copied from the droplet crontab in `FACTS.md`:
    - `php cron/run.php` every minute (the internal locks already throttle `constantly`/`daily`)
    - plus `php cron/ai.php` on whatever schedule production used
- [ ] **Step 3: Verify:**
  - `curl -s -o /dev/null -w '%{http_code}' localhost:8080/cronjobs.php` → `404` (no file, dispatch can't route it to anything)
  - same for `/setup.php`
  - `docker compose exec web php cron/run.php` → prints `Running ../cron/constantly.php`, without errors
- [ ] **Step 4:** check `public/underworld.php` and `public/cache.php` for state-changing actions anyone could trigger without logging in. Guard the same way, or leave them if they're read-only. **Commit** "Run cron and setup from CLI only".

### Task 13: Persistent user files

**Files:**
- Modify: wherever `PUBLIC_PATH.'signs/'` is written (`src/Dolumar/Pages/Customsign.php`, `src/Dolumar/Pages/Admin/Bonusbuilding.php`); `.gitignore`

- [ ] **Step 1:** the signs directory comes from the `SIGNS_PATH` constant (env, defaulting to `PUBLIC_PATH.'signs/'`). Dokku mounts persistent storage at `/app/public/signs`, so the default stays correct. Make sure the code creates the directory if it's missing. Ignore `public/signs/` in git.
- [ ] **Step 2:** extract the signs tar from the snapshot into `public/signs` locally. Then load a page that shows a custom sign and confirm it renders. **Commit** "Keep custom signs on persistent storage".

### Task 14: Secrets in git history + quick hole scan

- [ ] **Hole scan:** in both repos, grep for `unserialize(`, `eval(`, `assert(`, `preg_replace(.*/e`, and `include`/`require` whose path contains a variable, plus SQL strings concatenated with `$_GET`/`$_POST`/`$_REQUEST`/`$_COOKIE`/`$sInputs` without `escape`/`intval`. For each hit reachable without logging in: fix it (cast to int, `escape()`, whitelist, or `json_decode` in place of `unserialize`), and add a PHPUnit test if it lives in a function. Hits that are only reachable after login go on a list for the follow-up security review; don't fix them here. **Commit** "Fix unauthenticated injection points".
- [ ] Run `gitleaks detect --source . --log-opts="--all"` on both repos (and php-openid if it's still used), and write the findings to `~/Workbench/dolumar-data/gitleaks.md`. **Report every real secret to the owner** (which credential, which commit) and ask them to rotate it. Don't rewrite public history unless the owner asks.

### Task 15: Local end-to-end smoke test against the production copy

**Files:**
- Create: `tools/smoke.sh`

- [ ] **Step 1:** `tools/smoke.sh BASE_URL [COOKIE]` curls a fixed list of URLs:
  - `/`, `/oauth2/login` (expect 302 to the accounts URL)
  - the map tile/image endpoints the client loads on startup (read them from the page's JS init)
  - one dialog route
  - `/test/info` (expect 404 anonymous)
  - `/cronjobs.php` and `/setup.php` (404)
  
  It fails if any status is unexpected, or if any body matches `Deprecated:|Warning:|Notice:|Fatal error|Stack trace`.
- [ ] **Step 2:** ask the owner to add redirect URIs `http://localhost:8080/oauth2/login/next` and `https://<temp-host>/oauth2/login/next` to the CatLab accounts OIDC client, and put the client ID and secret in `.env.docker`. Then, in a browser:
  1. Log in as the owner's account.
  2. Check that the existing player's village shows with the same name, resources and buildings as in the snapshot (compare 3 values against SQL).
  3. Scroll the map; it must load and show cache hits (`docker compose exec memcached sh -c 'echo stats | nc localhost 11211'` → `get_hits > 0`).
  4. Queue a building, run `php cron/run.php`, and see progress.
  5. Send yourself an in-game message containing `é ⚔`; it must show up unchanged.
- [ ] **Step 3:** run `tools/smoke.sh http://localhost:8080` → exit 0. **Commit** "Add smoke test".

### Task 16: Game PR

- [ ] Switch composer to engine `^2.0` once tagged, then `composer update catlabinteractive/dolumar-engine`. Re-run smoke.
- [ ] Push branch `php8-dokku-migration` to `origin` (Bitbucket) and open a PR into master. Ask the owner whether the GitHub mirror should get it too.

## Phase 4: New droplet and cutover

### Task 17: Provision Dokku on the new droplet

- [ ] **Step 1:** the owner creates the droplet (Ubuntu 24.04 LTS, ≥2 GB RAM) and gives SSH access. **Ask before connecting.**
- [ ] **Step 2:** set up the server:
  - install the latest stable Dokku (official bootstrap script)
  - install the plugins: `dokku-mysql`, `dokku-memcached`, `dokku-letsencrypt`
  - enable `ufw` with only 22/80/443 open
  - turn on unattended-upgrades
- [ ] **Step 3:** create and link the app:
  - `dokku apps:create dolumar`
  - `dokku mysql:create dolumar-db` (image matching the snapshot's MySQL major version, or 8.0) and `mysql:link`
  - `dokku memcached:create dolumar-cache` and `memcached:link`
- [ ] **Step 4:** config and storage:
  - set config with `dokku config:set --no-restart dolumar ...`: every key from `.env.example` with production values from `FACTS.md`, plus `PROTOCOL=https`, `ABSOLUTE_URL=https://game.dolumar.com/`
  - `dokku storage:ensure-directory dolumar-signs` and `storage:mount dolumar /var/lib/dokku/data/storage/dolumar-signs:/app/public/signs`
- [ ] **Step 5:** add a temporary domain, e.g. `dolumar.<droplet-ip>.sslip.io`, via `dokku domains:set dolumar`.

### Task 18: Rehearsal on the temporary domain

- [ ] **Step 1:** import the snapshot: `gunzip -c dolumar.sql.gz | ssh dokku mysql:import dolumar-db`. Compare row counts against `rowcounts.txt`.
- [ ] **Step 2:** copy the signs into the storage dir, with ownership `32767:32767` (the Dokku herokuish user).
- [ ] **Step 3:** `git remote add dokku dokku@<droplet>:dolumar && git push dokku php8-dokku-migration:master`. In the build log, confirm the buildpack installed `PHP 8.4.x` with x ≥ 27. If it's older, update the buildpack (`dokku builder-herokuish:set` / `buildpacks:set` to the latest `heroku/php`) and push again.
- [ ] **Step 4:** run `tools/smoke.sh https://<temp-domain>` → exit 0. Repeat the manual login/village/map/cron/message checks from Task 15 step 2. Check `dokku cron:list dolumar` shows the jobs, and `dokku logs dolumar` shows cron runs without errors.

### Task 19: Cutover and retiring the old droplet

- [ ] **Step 1:** ask the owner to lower the TTL for game.dolumar.com (if it's above 300s) and to power on the old droplet. **Ask before SSH.** Take a fresh dump the same way as Task 0. If its row counts are identical to the snapshot, skip re-importing; otherwise drop the DB, re-import, and recount.
- [ ] **Step 2:** on the old droplet, stop and disable the web server and PHP (`systemctl disable --now apache2 php*-fpm nginx`, whichever exist) and keep MySQL as it is. Check with `curl -I http://<old-ip>/` → connection refused. The owner powers the droplet off and decides later when to destroy it.
- [ ] **Step 3:** the owner points game.dolumar.com's A record at the new droplet. Once DNS resolves: `dokku domains:set dolumar game.dolumar.com`, `dokku letsencrypt:set dolumar email thijs@catlab.be`, `dokku letsencrypt:enable dolumar`, `dokku letsencrypt:cron-job --add`.
- [ ] **Step 4:** run `tools/smoke.sh https://game.dolumar.com` → exit 0, and ask the owner to log in as the player. Done when the player's kingdom is there.

### Task 20: Wrap-up

- [ ] Update `README.md`: drop the Heroku button and instructions; add a Dokku deploy section (the commands from Task 17) and docker-based local dev.
- [ ] Remove `Procfile`-irrelevant Heroku bits from `app.json` (`addons`, `formation`), keep the `cron` + `scripts.dokku` sections, then commit and push to the PR.
- [ ] Tell the owner what's left over: `git stash list` still holds the old composer pin, the old droplet is powered off, the full security review is the next project, and the `dolumar-portal` move is a follow-up.

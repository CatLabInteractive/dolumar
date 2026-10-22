# Dolumar: PHP 8 + Dokku migration — design

Date: 2026-10-22
Status: draft, awaiting approval

## Intent and why

Dolumar (game.dolumar.com) is a live browser game with one active player. It is
currently **offline on purpose**: on 21 Oct 2026 php.net published two critical
(CVSS 9.2) unauthenticated remote-code-execution bugs in PHP itself,
CVE-2026-109618 and CVE-2026-109619. They affect every PHP installation serving
web requests and are fixed only in PHP 8.5.12, 8.4.27, 8.3.36 and 8.2.35
(source-only backports for 7.4–8.1; nothing for older).

Dolumar was written for PHP ~7.1 on top of libraries that are long dead, so there
is no "just patch PHP" option. To bring the game back online safely it has to run
on a patched PHP 8.x. At the same time the owner wants to move it off the
hand-managed DigitalOcean droplet and onto a new droplet running Dokku, where PHP is
installed by the buildpack and future security releases are a redeploy away.

Success = the one player logs in at game.dolumar.com and finds their kingdom
exactly as they left it, served by a patched PHP 8.x running in Dokku on the new Dolumar droplet,
and the old droplet no longer serves PHP to the internet.

## Goals

1. This repo (`dolumar`) and `catlabinteractive/dolumar-engine` run on PHP 8.4
   (minimum 8.4.27) without fatal errors, deprecations that break behaviour, or
   warnings leaking into output.
2. Dead / PHP-8-incompatible dependencies are upgraded or replaced.
3. The game is deployed as a new Dokku app on the new Dolumar Dokku droplet using the
   Heroku PHP buildpack (existing `Procfile`), configured entirely via env vars.
4. The game's MySQL database moves from the droplet into a Dokku MySQL service
   linked to the app, with all existing data intact.
5. Cron jobs run inside Dokku.
6. game.dolumar.com points at the Dokku app, with HTTPS.
7. The native PHP setup on the droplet is stopped from serving web traffic.
8. A short "obvious holes" pass before going live (see Security, below).

## Non-goals

- New features, gameplay or balance changes, UI redesign.
- Rewriting the engine onto a modern framework (Laravel etc.).
- A full security audit of all game/engine code — that is a separate follow-up
  project (see below).
- Supporting Heroku or the "Deploy to Heroku" button any more.

## Anti-goals (would be a failure even if "it works")

- The player's data is lost, corrupted, or silently altered (encoding/charset
  mangling counts).
- The game goes back online still reachable through an unpatched PHP — on the
  droplet or anywhere else.
- Web-reachable setup/maintenance endpoints that let an anonymous visitor reset
  or alter the game.
- Gameplay behaviour drifts because a library swap changed semantics (e.g. DB
  layer returning different types, rounding, dates/timezones).

## Current state (facts)

- `composer.json` requires `catlabinteractive/dolumar-engine` (GitHub,
  `git@github.com:CatLabInteractive/dolumar-engine.git`, public, latest tag v1.0.6),
  `airbrake/phpbrake ^0.2`, `openid/php-openid dev-master` (CatLab fork),
  `vlucas/phpdotenv ^2.4`. A `"platform": {"php": "^7.1.0"}` block sits at the
  top level of composer.json, where Composer and the buildpack ignore it.
- Engine requires PHPMailer ~5.2, MDB2 2.5.2-beta + mysqli driver (PEAR),
  `ivan-novakov/php-openid-connect-client ^0.2.1`, php-openid, and
  `ext-memcached`, `ext-mbstring`, `ext-gd`, `ext-bcmath`. Zend Framework 2.5–2.13
  components come in transitively.
- Engine: ~250 PHP files under `src/` (the "Neuron" game-server framework +
  Dolumar logic). This repo: ~350 PHP files. Engine has ~14 files using removed
  PHP functions (`mysql_*`, `ereg`, `create_function`, `each()`, `split()`).
- Config is already env-driven (`bootstrap/serverconfig-default.php`):
  `DATABASE_URL` (falls back to `CLEARDB_DATABASE_URL`), memcache via
  `MEMCACHIER_*`, SMTP via `EMAIL_SMTP_*` (falls back to `MAILGUN_*`), OpenID
  Connect via `OPENID_CONNECT_*`, plus CREDITS/SERVERLIST keys, `PROTOCOL`,
  `ABSOLUTE_URL`. `.env.example` lists them.
- Login uses OpenID Connect against CatLab accounts (recent commits: "new
  authentication system").
- Cron: `cron/constantly.php`, `cron/daily.php`, `cron/ai.php`, dispatched via
  `public/cronjobs.php`.
- `public/setup.php` (DB setup, run by `tools/setup.sh` on Heroku postdeploy) and
  `public/cronjobs.php` sit in the web root.
- `dump/dump.sql` is the schema/seed dump.
- Production today: native PHP + MySQL on a DigitalOcean droplet. Server is
  currently powered off. The live DB is the only copy of the player's data.
- Working tree on master has uncommitted changes pinning the engine to `~1.0.6`
  and an updated lock with older Zend versions. The owner doesn't know where they
  came from; see Claude's calls.
- External services: CatLab accounts (OIDC login) works. CatLab credits has been
  broken for a long time. master.dolumar.com (server list) is probably gone.
- Files the game writes to disk: custom sign images (`PUBLIC_PATH.'signs/'`,
  written by `Pages/Customsign.php` and `Pages/Admin/Bonusbuilding.php`),
  `version.txt`, and a cache in `/tmp/dolumar`. The droplet may also have a
  gitignored local config (`bootstrap/serverconfig.php`/`config.php`).

## Decided by the owner

- Target PHP 8.x patched for the CVEs; host on Dokku on a **new, dedicated DigitalOcean
  droplet** just for Dolumar. It is not fra001 (the game can't run there) and not
  the old droplet.
- MySQL moves into Dokku (dokku-mysql service), not left on the droplet.
- Claude may SSH to the old and new droplets, **but must ask first** each time,
  naming the server and what it will run, so the owner can power it on.
- Security cleanup of the old code is wanted, as a general review (no known
  specific exploit). The owner considers the code "written as a young developer".
- Work via feature branches + PRs, never commits on master (global preference).
- The owner changes DNS for game.dolumar.com.
- No GitHub token needed: `dolumar`, `dolumar-engine` and `php-openid` are all
  public on GitHub (GPL-3.0). The `repositories` entries in composer.json switch
  from `git@github.com:` SSH URLs to `https://github.com/` so the buildpack can
  fetch them without credentials.
- Credits and the server list are not needed. The game must work with
  `CREDITS_*` and `SERVERLIST_*` unset (paid features off, no server-list
  pings), without errors.

## Claude's calls (owner may override)

- **PHP 8.4** (`"php": "^8.4.27"` in `require`), not 8.5: wider library
  compatibility, supported until end of 2028. Remove the stray `platform` block.
- **Two repos change**: `dolumar` (this; `origin` is Bitbucket, public mirror on GitHub) and `dolumar-engine`
  (GitHub). Engine changes land as a PR + new tag (v2.0.0, since it drops PHP 7);
  this repo then requires `^2.0`.
- **Dependency direction:**
  - PHPMailer 5.2 → 6.x (small API change).
  - phpdotenv 2 → 5.
  - airbrake/phpbrake → latest, or drop if the owner no longer uses Airbrake/
    errbit (keep the integration optional on `AIRBRAKE_TOKEN`).
  - Zend Framework 2 → Laminas equivalents only where actually used; first find
    which Zend components are used directly vs only by the OIDC client.
  - `ivan-novakov/php-openid-connect-client` (abandoned, pulls Zend) → a
    maintained OIDC client (e.g. `jumbojett/openid-connect-php`) **if** it is the
    thing blocking PHP 8; otherwise keep and patch.
  - php-openid (legacy OpenID 2.0): remove if no longer used for login since the
    move to OpenID Connect; otherwise keep the fork and fix it for PHP 8.
  - MDB2/PEAR: the riskiest swap. Preferred: keep the engine's DB facade API and
    reimplement it on PDO (MySQL) so call sites don't change. Fall back to a
    patched MDB2 only if the facade is too leaky.
- **Memcached** (owner's decision: caching is required, the map depends on it
  heavily): a `dokku-memcached` service linked to the app, with `ext-memcached`
  enabled in the buildpack. The env var the service provides is mapped to
  `MEMCACHE_SERVERS`. Memcachier SASL username/password stay optional. Check also
  whether the map's file cache (`/tmp/dolumar`) should go on persistent storage
  or can be rebuilt after each deploy.
- **Mail**: keep SMTP via `EMAIL_SMTP_*` env vars; the owner supplies the SMTP
  credentials they use today.
- **Cron**: Dokku `app.json` `cron` entries calling the cron PHP scripts via CLI
  (not via a public URL). Frequencies copied from the droplet's crontab.
- **Setup**: `app.json` `scripts.dokku.predeploy`/`postdeploy` runs schema
  migrations via CLI; `public/setup.php` and `public/cronjobs.php` are no longer
  web-reachable (moved out of `public/` or guarded to CLI only).
- **Uncommitted composer changes**: stash them (`git stash`, not deleted), and
  build engine v2.0.0 on top of engine master, so the session fix `64a98c1` is
  included.
- **Disk state**: during step 1, list what the droplet has on disk besides the
  code. Signs and any other user-made files go to a Dokku persistent storage
  mount and are copied over in steps 1 and 5. A local config file on the droplet
  is read, its values turned into env vars, and it is not deployed.
- **Production revision**: during step 1, diff the droplet's code against this
  repo's master and the engine tags. If production has changes that aren't
  committed, or uses a different login (OpenID 2.0 vs OIDC), stop and ask the
  owner before porting, because players are looked up by OpenID identity.
- **Testing login**: ask the owner to add redirect URIs for local dev and the
  temporary Dokku hostname to the OIDC client (or create a dev client) before
  step 2.
- **TLS**: dokku-letsencrypt for game.dolumar.com.
- **Charset**: the DB dump is taken and imported with an explicit charset matching
  the source tables (check with `SHOW CREATE TABLE`), and verified by comparing
  row counts plus a spot check of text with non-ASCII characters.
- **Local verification**: a local Docker/compose (or local PHP 8.4 + MySQL) setup
  loaded with a copy of the production dump, so PHP 8 work is tested against the
  real data before anything goes to the new droplet. Production data copies stay out of
  git.

## Rollout order

1. Get a copy of the production DB (one SSH session to the droplet, asked first)
   and the droplet's crontab, vhost config, PHP extensions and env/config values.
2. Port engine + this repo to PHP 8.4 locally against that copy; click through
   login, the player's kingdom, building, training, battle/cron ticks.
3. Pre-launch security pass (below).
4. The owner creates the new droplet. Claude installs Dokku + the mysql and
   letsencrypt plugins (asked first), creates the app + MySQL service, import the dump,
   deploy, test on a temporary hostname.
5. Cutover: fresh final dump from the droplet (game has been offline, so the copy
   from step 1 is likely identical — confirm), re-import, switch DNS for
   game.dolumar.com, issue TLS cert.
6. Disable Apache/nginx + PHP on the droplet (keep the droplet and its DB around
   powered off for a rollback window, owner decides when to destroy).

## Pre-launch security pass (in scope)

Narrow, aimed at "don't relaunch with something trivially exploitable". The
code is public, so anyone can read it to find holes:
- Web-reachable admin/setup/cron/dump endpoints (`public/setup.php`,
  `public/cronjobs.php`, `public/underworld.php`, `dump/` not in web root, etc.).
- Unauthenticated entry points: SQL built by string concatenation from request
  input, file includes from request input, `unserialize` of user input, `eval`.
- Session cookie flags (Secure, HttpOnly, SameSite) and session fixation on login.
- Secrets not committed in the repo, including git history of the public repos.
  Any secret found there gets rotated.

## Follow-up project (out of scope here)

Full security review of engine + game: XSS in templates, CSRF on state-changing
actions, authorization checks (acting on other players' villages), remaining SQL
injection, rate limiting. Separate design when this migration is done.

## Left to the builder

- Exact library versions and replacement choices within the direction above.
- How to structure the PHP 8 port (tooling such as Rector/PHPStan is fine).
- Local dev environment shape.
- How to split the work into PRs.

## Open questions

None blocking. `dolumar-portal` will eventually also run on the new Dolumar
Dokku droplet. Moving it there is a separate follow-up, not part of this
project (Claude's call).

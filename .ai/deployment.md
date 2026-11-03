# Deployment

Production runs as a Dokku app (Heroku PHP buildpack, `Procfile`: `heroku-php-apache2 public/`) on a dedicated
DigitalOcean droplet, with linked `dokku-mysql` and `dokku-memcached` services and HTTPS from `dokku-letsencrypt`.
The full server setup is in the root `README.md`; the history of how it got there is in
[`superpowers/`](superpowers/).

Deploying changes production for a live player. Agents do not deploy, push to the `dokku` remote, or run commands on
the servers unless the owner asks for that specific action.

## What happens on deploy

- `app.json` `scripts.dokku.postdeploy` runs `tools/postdeploy.sh`, which runs `php tools/setup.php` (and, on preview
  instances only, `tools/seed.php`). `setup.php` installs the schema (`dump/dump.sql`) when the `n_players` table
  does not exist; otherwise it runs every `dump/upgrades/*.sql` in name order. Those files are idempotent, and a
  failure fails the deploy. See [`dump/upgrades/README.md`](../dump/upgrades/README.md).
- `app.json` `cron` installs the cron jobs: `php cron/run.php` every minute and `php cron/ai.php` every 15 minutes.
- Composer installs dependencies from `composer.lock`, so the engine version deployed is the commit pinned there.

## Configuration

All configuration is environment variables. [`.env.example`](../.env.example) is the authoritative list; when you
add a setting, add it there (with a comment) and to the README's configuration section.

Key variables: `DATABASE_URL`, `MEMCACHED_URL`, `ABSOLUTE_URL`, `PROTOCOL`, `EMAIL_SMTP_*`, `EMAIL_FROM*`,
`OPENID_CONNECT_*`, `APP_DEBUG` (never set in production), `SIGNS_PATH`, `CACHE_DIR`, `AIRBRAKE_*`,
`VAPID_*` (browser notifications; generated once with `php tools/vapid-keys.php` and never rotated, since a new pair
breaks every existing subscription).
`NPC_AGGRESSION` and `NPC_PROTECTION_RUNES` (NPC tribes, see architecture.md).

Error reporting (`AIRBRAKE_*`) goes through `Dolumar_ErrorReporting_Notifier`, a phpbrake subclass. phpbrake drops a
failed delivery silently, so the subclass logs it (`Error tracker: ...` in `dokku logs`): check there first when
errors do not show up in Errbit.

Paid speed-ups (spend credits to finish a building, training or scouting queue sooner) are off unless
`PREMIUM_SPEEDUP_BUILDINGS`, `PREMIUM_SPEEDUP_TRAINING` or `PREMIUM_SPEEDUP_SCOUTING` is set to `1`. When off,
`Dolumar_Windows_Statusbar::canSpeedUp()` hides the link and the `Speedup` window refuses the request.

`CREDITS_*` and `SERVERLIST_*` belong to dead CatLab services. They stay unset and the game must keep working
without them: guard any code that would call them.

## Persistent files

The container filesystem is thrown away on every deploy. Anything that must survive goes in a Dokku storage mount.
Today that is only `public/signs` (custom sign images, mounted from `/var/lib/dokku/data/storage/dolumar-signs`).
If a change writes new user files to disk, it needs a new mount and a README update.

## Pull request previews

Every same-repository PR gets a throwaway instance on the production Dokku server (`dolumar-pr-<n>`, at
`pr-<n>.preview.dolumar.com`), built by `.github/workflows/preview.yml` and removed when the PR closes. Pushes to `develop` update a
standing `dolumar-develop` preview that keeps its data. The setup and
the security model are in the README section "Pull request previews". In short:

- The GitHub key only reaches `tools/preview/dolumar-preview` (forced SSH command). That script is the only place that
  names preview apps, services and databases; keep it unable to touch anything other than `dolumar-pr-*` /
  `pr_*` and the `dolumar-<branch>` / `branch_*` previews of branches in its `PREVIEW_BRANCHES` setting.
  It is copied to the server by hand, so a change to it needs the owner to reinstall it (README step 3, which is
  safe to re-run). Its settings (domain, Let's Encrypt email, Errbit key) live only on the server in
  `/etc/default/dolumar-preview`; keep real values out of the repository.
- `PREVIEW_MODE=1` turns on `tools/seed.php` and `public/preview-login.php` (log in as any player, no OIDC). Both must
  keep refusing to act without it. Never set it in production.
- Previews report errors to Errbit only when `PREVIEW_AIRBRAKE_TOKEN` (and `PREVIEW_AIRBRAKE_HOST`) are set in the
  script's config file; the script copies them into every preview on each deploy, with
  `AIRBRAKE_ENVIRONMENT=preview-<name>`. Without them a preview's errors only appear in `dokku logs <app>`.
- When you open a PR that needs a manual check, point the owner at the preview link in the PR comment.

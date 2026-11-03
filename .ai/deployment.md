# Deployment

Production runs as a Dokku app (Heroku PHP buildpack, `Procfile`: `heroku-php-apache2 public/`) on a dedicated
DigitalOcean droplet, with linked `dokku-mysql` and `dokku-memcached` services and HTTPS from `dokku-letsencrypt`.
The full server setup is in the root `README.md`; the history of how it got there is in
[`superpowers/`](superpowers/).

Deploying changes production for a live player. Agents do not deploy, push to the `dokku` remote, or run commands on
the servers unless the owner asks for that specific action.

## What happens on deploy

- `app.json` `scripts.dokku.postdeploy` runs `tools/postdeploy.sh`, which runs `php tools/setup.php` (and, on preview
  instances only, `tools/seed.php`). `setup.php` is idempotent and only installs the schema
  when the `n_players` table does not exist. It never migrates an existing database.
- `app.json` `cron` installs the cron jobs: `php cron/run.php` every minute and `php cron/ai.php` every 5 minutes.
- Composer installs dependencies from `composer.lock`, so the engine version deployed is the commit pinned there.

## Configuration

All configuration is environment variables. [`.env.example`](../.env.example) is the authoritative list; when you
add a setting, add it there (with a comment) and to the README's configuration section.

Key variables: `DATABASE_URL`, `MEMCACHED_URL`, `ABSOLUTE_URL`, `PROTOCOL`, `EMAIL_SMTP_*`, `EMAIL_FROM*`,
`OPENID_CONNECT_*`, `APP_DEBUG` (never set in production), `SIGNS_PATH`, `CACHE_DIR`, `AIRBRAKE_*`.

Scripts and stylesheets are loaded with `?version=APP_VERSION`. The bootstrap takes that from `APP_VERSION` or,
by default, from `GIT_REV`, which Dokku sets to the deployed commit, so every deploy busts the browser cache. The
engine's fallback is the repo's SVN-era `version.txt`, which never changes; a deployment without either variable
serves stale assets after an update.

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
  It is copied to the server by hand, so a change to it needs the owner to reinstall it.
- `PREVIEW_MODE=1` turns on `tools/seed.php` and `public/preview-login.php` (log in as any player, no OIDC). Both must
  keep refusing to act without it. Never set it in production.
- When you open a PR that needs a manual check, point the owner at the preview link in the PR comment.

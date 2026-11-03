# Dolumar
Play now at http://www.dolumar.com/

Dolumar is a browser-based strategy game (PBBG): create your own powerful kingdom in a vast, colorful, virtual world.
It is a PHP 8.4 web application that needs MySQL and Memcached, and it runs on Docker and Dokku.

## Local development
Requires Docker. The game depends on the `dolumar-engine` repository, which must be checked out next to this one
(`../dolumar-engine`, or set `ENGINE_PATH`).

```
cp .env.docker.example .env.docker
docker compose up -d --build
docker compose exec web composer install
docker compose exec web php tools/setup.php   # only needed for an empty database
```

* The game is served at http://localhost:8080, caught outgoing mail shows up in Mailpit at http://localhost:8025.
* `public/signs` must exist and be writable by the web container (`mkdir -p public/signs && chmod 777 public/signs`).
* Smoke test: `tools/smoke.sh http://localhost:8080`. If OpenID Connect is not configured, run it as
  `SMOKE_NO_OAUTH=1 tools/smoke.sh http://localhost:8080`.
* Game tests (the NPC decision logic, no database needed): `docker compose exec web vendor/bin/phpunit`.
* Engine tests: `docker compose exec -w /engine web vendor/bin/phpunit`.

## Configuration
All configuration is done through environment variables. [`.env.example`](.env.example) is the authoritative list;
the most important ones are:

* `DATABASE_URL`: MySQL connection, `mysql://user:password@host:3306/dolumar` (set by `dokku mysql:link`).
* `MEMCACHED_URL`: required, the map depends on it (set by `dokku memcached:link`).
* `ABSOLUTE_URL` and `PROTOCOL`: public URL of the game (with trailing slash) and `http` or `https`.
* `EMAIL_SMTP_SERVER`, `EMAIL_SMTP_PORT`, `EMAIL_SMTP_SECURE`, `EMAIL_SMTP_USERNAME`, `EMAIL_SMTP_PASSWORD`
  (plus `EMAIL_FROM`, `EMAIL_FROM_NAME`): SMTP credentials, needed for email validation. Any SMTP server works.
* `OPENID_CONNECT_*`: OpenID Connect login provider (authorize/token/profile URLs, client id and secret).
* `APP_DEBUG`: enables debug output; leave unset in production.
* `SIGNS_PATH`: where custom sign images are stored (defaults to `public/signs`). Must be persistent in production.
* `AIRBRAKE_TOKEN`, `AIRBRAKE_HOST`, `AIRBRAKE_PROJECT_ID`, `AIRBRAKE_ENVIRONMENT`: optional error reporting to Errbit
  or Airbrake. A notice the tracker does not accept is written to the error log (`dokku logs`), starting with
  `Error tracker:`.
* `PREMIUM_SPEEDUP_BUILDINGS`, `PREMIUM_SPEEDUP_TRAINING`, `PREMIUM_SPEEDUP_SCOUTING`: set to `1` to let players pay
  credits to speed up that queue. Off by default.
* `NPC_AGGRESSION`: how far the goblin tribes go when raided: `off` (they only build and defend), `counter`
  (default: scouts, an envoy, then counter-attacks against raiders), or `full` (also strikes against players who
  attack the tribe's allies). `NPC_PROTECTION_RUNES` (default 10): players with fewer runes are never attacked,
  nor are accounts younger than seven days.

`CREDITS_*` and `SERVERLIST_*` are optional and off by default; the game works without them.

## NPC tribes
Every player gets a goblin village nearby, run by `cron/ai.php` every five minutes. A tribe hoards resources,
keeps a hidden standing per player and reacts to how it is treated: raid it and it sends scouts, then an envoy
with a price, then war parties; pay the envoy or beat a war party at home and you get a truce. Gifts and
delivering the request shown on its village profile make it friendly, and friends get goblin reinforcements
when attacked. The design is in
[`.ai/superpowers/specs/2026-11-02-npc-standing-ai-design.md`](.ai/superpowers/specs/2026-11-02-npc-standing-ai-design.md);
the code lives in `src/Dolumar/Npc/` with every tunable number in `Dolumar_Npc_Config`. The three `npc_*`
tables come from the migration `dump/migrations/upgrade-20261102-npc-tribes.sql`.

## Deploying on Dokku
On the server (Dokku with the `dokku-mysql`, `dokku-memcached` and `dokku-letsencrypt` plugins installed):

```
dokku apps:create dolumar
dokku mysql:create dolumar-db
dokku mysql:link dolumar-db dolumar
dokku memcached:create dolumar-cache
dokku memcached:link dolumar-cache dolumar

# every key from .env.example you need, plus the public URL
dokku config:set --no-restart dolumar PROTOCOL=https ABSOLUTE_URL=https://YOUR_DOMAIN/ EMAIL_SMTP_SERVER=... OPENID_CONNECT_CLIENT_ID=...

# persistent storage for custom signs
dokku storage:ensure-directory dolumar-signs
dokku storage:mount dolumar /var/lib/dokku/data/storage/dolumar-signs:/app/public/signs

dokku domains:set dolumar YOUR_DOMAIN
```

To migrate an existing database instead of starting empty, import a dump before the first deploy:
`dokku mysql:import dolumar-db < dump.sql`.

Then push the repository (`git remote add dokku dokku@YOUR_SERVER:dolumar && git push dokku <branch>:master`) and
enable HTTPS. DNS for `YOUR_DOMAIN` must already point at the server:

```
dokku letsencrypt:set dolumar email you@example.com
dokku letsencrypt:enable dolumar
dokku letsencrypt:cron-job --add
```

* The `postdeploy` script in [`app.json`](app.json) runs [`tools/postdeploy.sh`](tools/postdeploy.sh) on every deploy,
  which runs `php tools/setup.php`. It is idempotent and only installs the schema into an empty database.
* Schema changes are migration files in `dump/migrations/`, run by `php tools/migrate.php` as the Dokku predeploy
  script (see [`dump/README.md`](dump/README.md)).
* The cron jobs (`cron/run.php` every minute, `cron/ai.php` every 5 minutes) are also defined in `app.json`
  and are installed by Dokku automatically.
* Check a deployment with `tools/smoke.sh https://YOUR_DOMAIN`.

## Pull request previews
Every pull request from a branch in this repository can get its own instance at `https://pr-<number>.preview.dolumar.com/`,
with test players already in it. It is created by [`.github/workflows/preview.yml`](.github/workflows/preview.yml)
when the pull request opens, updated on every push, and removed (app, memcached and database) when the pull request
closes. A daily run also removes previews whose pull request is no longer open.

Pushes to `develop` deploy a standing preview at `https://develop.preview.dolumar.com/`. Unlike pull request previews it
keeps its database between deploys (it is only seeded once, when empty) and is never removed automatically. To reset it,
run `dolumar-preview destroy develop` on the server; the next push recreates it. Other branches can get one too: add
them to `PREVIEW_BRANCHES` on the server and to `on.push.branches` in the workflow.

The GitHub key cannot run Dokku commands. It logs in as a separate `dolumar-preview` user whose only allowed command is
[`tools/preview/dolumar-preview`](tools/preview/dolumar-preview), which accepts `deploy <pr|branch> <sha>`, `destroy <pr|branch>` and
`list`, and only touches `dolumar-pr-<n>` and `dolumar-<branch>` apps (branches from `PREVIEW_BRANCHES` only), their
`-cache` memcached services and their `pr_<n>` / `branch_<name>` databases in a separate `dolumar-previews` MySQL
service. Production apps and services are never named.

A preview runs with `PREVIEW_MODE=1`, which makes `postdeploy.sh` run [`tools/seed.php`](tools/seed.php) (12 players
`Tester1`..`Tester12` with villages, a clan and NPC tribes) and enables `/preview-login.php`, where you log in as any
player without OpenID Connect. Both refuse to do anything without `PREVIEW_MODE=1`, so never set it in production.

### One-time server setup
Needs Dokku 0.24 or newer (for `git:from-archive`) with the mysql, memcached and letsencrypt plugins. As root:

```
# 1. DNS: point a wildcard record *.preview.dolumar.com at the server.

# 2. A MySQL service shared by all previews (each preview gets its own database and user in it).
dokku mysql:create dolumar-previews

# 3. The script. Safe to re-run: this is also how you update it, and it never touches the settings from step 4.
# REF is a branch or commit sha; GitHub caches raw files for 5 minutes, so right after a merge use the commit.
# Download to a temporary file and move it into place: overwriting the file in place breaks a deploy that is running
# it at that moment (bash reads scripts while executing them).
REF=develop
curl -fsSL "https://raw.githubusercontent.com/CatLabInteractive/dolumar/$REF/tools/preview/dolumar-preview" \
  -o /usr/local/bin/dolumar-preview.new
chmod 755 /usr/local/bin/dolumar-preview.new && mv /usr/local/bin/dolumar-preview.new /usr/local/bin/dolumar-preview

# 4. The settings. They live only on this server: written once, edited by hand afterwards. The [ -f ] guard means
# re-running this never overwrites them.
[ -f /etc/default/dolumar-preview ] || cat > /etc/default/dolumar-preview <<'CONF'
PREVIEW_DOMAIN=preview.dolumar.com
PREVIEW_LETSENCRYPT_EMAIL=you@example.com
# PREVIEW_HTTPS=0       # plain http, skips Let's Encrypt
# PREVIEW_MEMORY=512m   # memory limit per preview
# PREVIEW_BRANCHES=develop  # branches with a standing preview
# Errbit for the previews (each preview reports as environment preview-pr-<n> / preview-<branch>):
# PREVIEW_AIRBRAKE_HOST=https://errors.catlab.eu
# PREVIEW_AIRBRAKE_TOKEN=<project API key>
# PREVIEW_AIRBRAKE_PROJECT_ID=
CONF

# 5. A user that can only run that script, through sudo.
useradd --create-home --shell /bin/bash dolumar-preview
cat > /etc/sudoers.d/dolumar-preview <<'SUDO'
Defaults!/usr/local/bin/dolumar-preview env_keep += "SSH_ORIGINAL_COMMAND"
dolumar-preview ALL=(root) NOPASSWD: /usr/local/bin/dolumar-preview
SUDO
chmod 440 /etc/sudoers.d/dolumar-preview && visudo -c

# 6. A key pair for GitHub. The forced command means the key can run nothing else.
ssh-keygen -t ed25519 -N '' -C github-dolumar-preview -f /root/dolumar-preview-key
install -d -m 700 -o dolumar-preview -g dolumar-preview /home/dolumar-preview/.ssh
echo "command=\"sudo /usr/local/bin/dolumar-preview\",restrict $(cat /root/dolumar-preview-key.pub)" \
  > /home/dolumar-preview/.ssh/authorized_keys
chown dolumar-preview: /home/dolumar-preview/.ssh/authorized_keys && chmod 600 /home/dolumar-preview/.ssh/authorized_keys
```

Then add three repository secrets (Settings → Secrets and variables → Actions):

* `PREVIEW_SSH_HOST`: the server's host name.
* `PREVIEW_SSH_KEY`: the contents of `/root/dolumar-preview-key` (the private key from step 6).
* `PREVIEW_SSH_KNOWN_HOSTS`: the output of `ssh-keyscan <host>`.

Once `PREVIEW_SSH_KEY` is saved, delete that private key from the server with `shred -u /root/dolumar-preview-key`, so
GitHub holds the only copy. Keep `/root/dolumar-preview-key.pub` and the `authorized_keys` entry.

Until `PREVIEW_SSH_HOST` is set the workflow does nothing. When `tools/preview/dolumar-preview` changes, run step 3
again and check with `grep` that you got the new version. To remove a preview by hand: `dolumar-preview destroy <number>`.

Each preview uses roughly 150 to 300 MB (Apache/PHP plus a small memcached) and runs the game's cron jobs. The
database server is shared. Keep an eye on memory if many pull requests are open at once.

## OpenID Connect
* Redirect URL: https://YOUR_DOMAIN/oauth2/login/next

## CatLab Credits
Paid features used to run on the CatLab Credits framework. That service is not currently available, so leave
`CREDITS_*` unset.

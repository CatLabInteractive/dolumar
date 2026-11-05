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
* Game tests: `docker compose exec web vendor/bin/phpunit`. The `unit` suite (NPC decision logic) needs no database;
  the `integration` suite runs against the Docker database.
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
* `APP_VERSION`: cache-busting version appended to script and stylesheet URLs. Defaults to the deployed git commit
  (`GIT_REV`, set by Dokku on every deploy); only set it when deploying without git.
* `SIGNS_PATH`: where custom sign images are stored (defaults to `public/signs`). Must be persistent in production.
* `TRUSTED_PROXIES`: comma separated CIDR ranges of reverse proxies whose `X-Forwarded-For` / `X-Real-IP` is
  trusted for the visitor's IP (multi-account check, login log). Defaults to loopback and `172.16.0.0/12`.
* `AIRBRAKE_TOKEN`, `AIRBRAKE_HOST`, `AIRBRAKE_PROJECT_ID`, `AIRBRAKE_ENVIRONMENT`: optional error reporting to Errbit
  or Airbrake. A notice the tracker does not accept is written to the error log (`dokku logs`), starting with
  `Error tracker:`.
* `VAPID_PUBLIC_KEY`, `VAPID_PRIVATE_KEY` (and optionally `VAPID_SUBJECT`, a `mailto:` or `https:` contact): browser
  notifications. Generate the pair once with `php tools/vapid-keys.php`, set both, and never change them: a new pair
  silently invalidates every player's subscription. Without them notifications are off.
* `PREMIUM_SPEEDUP_BUILDINGS`, `PREMIUM_SPEEDUP_TRAINING`, `PREMIUM_SPEEDUP_SCOUTING`: set to `1` to let players pay
  credits to speed up that queue. Off by default.
* `NEVERENDING`: set to `1` for a world without an endgame. The Dor Daedeloth spell is never offered, no clan can
  win and the server is never reset. Players see a notice on the welcome screen, the ranking, the race selection
  and the server settings. Off by default.
* `SERVER_NAME`: the world's name, shown after "Dolumar" in the browser tab title (`x3 Speed` gives
  "Dolumar x3 Speed") and on the launch and end-of-round pages. It is applied on the next page load and survives a
  reset. Unset keeps the name in the database (`Dolumar` unless a server list assigned one).
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
tables are created by `dump/upgrades/20261102-npc-tribes.sql`. Moderators can follow every tribe's standings
and decisions on the admin page "NPC tribes" (`/admin/npctribes`).

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

* The `predeploy` script in [`app.json`](app.json) runs [`tools/predeploy.sh`](tools/predeploy.sh) on every deploy,
  before the new release goes live, which runs `php tools/setup.php`. It installs the schema into an empty database and otherwise runs every file in
  [`dump/upgrades/`](dump/upgrades/) (see its README), so a schema change is an idempotent upgrade file there plus
  the same change in `dump/dump.sql`. A failing upgrade aborts the deploy and the previous release keeps running.
  To run it by hand: `dokku run <app> php tools/setup.php`.
* The cron jobs (`cron/run.php` every minute, `cron/ai.php` every 5 minutes) are also defined in `app.json`
  and are installed by Dokku automatically.
* Check a deployment with `tools/smoke.sh https://YOUR_DOMAIN`.

## Running several worlds
Each world (game server) is its own Dokku app with its own database, memcached service, sign storage, domain and
cron jobs, all built from the same commit. What makes worlds different is only their Dokku config:

| Setting | Long-running world | Regular world | Speed world |
|---------|--------------------|---------------|-------------|
| `NEVERENDING` | `1` | unset | unset |
| `SPEED_FACTOR` | unset (x1) | unset (x1) | `3` |
| `SERVER_NAME` | your choice | your choice | e.g. `x3 Speed` |
| `ROUND_DAYS` | ignored | your choice (default 180) | your choice (default 180) |
| `AIRBRAKE_ENVIRONMENT` | `production` | `production-classic` | `production-speed` |

`SPEED_FACTOR` sets resources, buildings and effects to that speed and training to twice that; troop movement stays
x1 unless `GAME_SPEED_MOVEMENT` is set (the `GAME_SPEED_*` settings each override one part). Players see the
speeds in the server settings window. Set the speed before a world opens: changing it on a running world changes
live timers and balance.

A new world, with `dolumar-speed` as the example (as root on the server):

```
dokku apps:create dolumar-speed
dokku mysql:create dolumar-speed-db
dokku mysql:link dolumar-speed-db dolumar-speed
dokku memcached:create dolumar-speed-cache
dokku memcached:link dolumar-speed-cache dolumar-speed
dokku storage:ensure-directory dolumar-speed-signs
dokku storage:mount dolumar-speed /var/lib/dokku/data/storage/dolumar-speed-signs:/app/public/signs
dokku domains:set dolumar-speed speed.dolumar.com

# the same settings as the existing world (SMTP, OpenID Connect, credits, VAPID keys, Errbit), see below,
# plus what is specific to this world:
dokku config:set --no-restart dolumar-speed PROTOCOL=https ABSOLUTE_URL=https://speed.dolumar.com/ \
  SPEED_FACTOR=3 SERVER_NAME="x3 Speed" AIRBRAKE_ENVIRONMENT=production-speed
```

Copying the shared settings: `dokku config:export --format envfile dolumar` prints the existing world's settings.
Leave out `DATABASE_URL`, `MEMCACHED_URL`, `ABSOLUTE_URL`, `NEVERENDING`, `SERVER_NAME`, `GIT_REV` and the `DOKKU_*` keys (they
belong to that app) and set the rest on the new app. The VAPID keys can be the same pair on every world.

Then deploy it (below), which installs the schema into the empty database, and enable HTTPS with
`dokku letsencrypt:enable dolumar-speed`. Add `https://speed.dolumar.com/oauth2/login/next` as a redirect URL of the
OpenID Connect client in CatLab Accounts, or login fails on the new world.

### Deploying every world
[`tools/deploy.sh`](tools/deploy.sh) pushes one commit (default `origin/master`) to every world, one app after the
other, and stops at the first failure. Run it from a checkout on a machine whose SSH key can push to Dokku:

```
DOLUMAR_DOKKU_HOST=dokku@YOUR_SERVER tools/deploy.sh            # origin/master to every world
tools/deploy.sh origin/develop --only dolumar-speed            # one world, another ref
```

It shows the commit and what each app runs now, asks for confirmation (`-y` skips that), skips apps that already run
that commit and checks that each world answers over HTTP before going on. The apps and their order come from
`DOLUMAR_APPS` (default `dolumar-speed dolumar-classic dolumar`); keep the long-running world last, so a broken
schema upgrade fails on a young world first. Both settings can go in `tools/deploy.env` (not committed; copy
[`tools/deploy.env.example`](tools/deploy.env.example)). Bash sources that file, so quote the app list:

```
DOLUMAR_DOKKU_HOST=dokku@YOUR_SERVER
DOLUMAR_APPS="dolumar-speed dolumar-classic dolumar"
```

A branch name (`master`, `origin/develop`) always deploys that branch as it is on GitHub now, never a stale local
copy, and the script refuses a commit whose `composer.json` does not require PHP 8 (anything from before the PHP 8
port, which cannot build on Dokku). It also warns when the commit lacks commits that are on `develop`: merge
`develop` into `master` before a release.

## Pull request previews
Every pull request from a branch in this repository can get its own instance at `https://pr-<number>.preview.dolumar.com/`,
with test players already in it. It is created by [`.github/workflows/preview.yml`](.github/workflows/preview.yml)
when the pull request opens, updated on every push, and removed (app, memcached and database) when the pull request
closes. A daily run (or a manual run of the Preview workflow) also removes previews whose pull request is no longer open,
including the memcached service or database a failed destroy left behind; its log lists what it kept and removed.
Closing or merging a pull request while its preview is still deploying is safe: the destroy waits for the deploy to
finish (same concurrency group in the workflow, and a per-preview lock in the server script for the daily cleanup and
manual runs), then removes it and marks the preview comment as removed.

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

## Credits (CatLab Accounts)
Paid features (premium, runes, resources, moving villages and buildings, bonus buildings, speed-ups) are bought with
Dolumar credits on [CatLab Accounts](https://accounts.catlab.eu/). Credits are enabled only when
`OPENID_CONNECT_CLIENT_ID`, `OPENID_CONNECT_SECRET` and `CATLAB_CREDITS_TYPE_ID` are all set; otherwise every
purchase link is hidden. `OPENID_CONNECT_PROFILE_URL_PREFIX` is also required when credits are enabled: the credits
code uses it to map players to their Accounts account. The old `CREDITS_*` settings stay unset.

Setup:

1. In the Accounts admin, create a "Dolumar" product.
2. Create a "Dolumar Credits" credit type pointing at that product. The price is EUR 0.014 per credit including VAT
   (500 credits = EUR 7.00). On the admin pricing page (`admin/credit-types/{id}/pricing`) enter **0.014** in the
   `price_incl_vat` field (EUR per credit, including VAT). Accounts stores it excluding VAT (0.011570); the preview
   should show 500 credits at about EUR 7.01 for EU buyers.
3. Enter the discount tiers in the same form's textarea, one per line: `100=1.00`, `1000=0.95`, `2000=0.91`,
   `3000=0.86`, `5000=0.83`.
4. Link Dolumar's OIDC client to the product and allow the `credits` scope. Do this (steps 1-4) **before** setting
   `CATLAB_CREDITS_TYPE_ID` in step 5: as soon as that is set the game asks for the `credits` scope at login, and
   Accounts refuses the login while the client is not allowed that scope.
   A newly created OIDC client also needs a signing key (a row in `oauth2_public_keys`); the existing production
   client already has one.
5. On the game server:

   ```
   dokku config:set dolumar CATLAB_CREDITS_TYPE_ID=<credit type id>
   # optional, defaults to https://accounts.catlab.eu/
   dokku config:set dolumar CATLAB_ACCOUNTS_URL=https://accounts.catlab.eu/
   ```

   `CATLAB_ACCOUNTS_URL` must be exactly Accounts' public URL (same scheme, host and path; a trailing slash is
   optional): the game only redirects players to confirm URLs that start with it, so any other spelling (`http`
   instead of `https`, `www.`, ...) makes every purchase fail with "temporarily unavailable".
6. Players log in once more to grant the `credits` scope. Until then, purchase links send them through login.

Pending purchases that missed their notification, and refunds that could not reach Accounts (`refund_pending`), are
settled by the `cron/constantly.php` job; a player's own purchases are also settled when they open the premium shop.

After the first real deploy, check the logs for `REFUND FAILED` and `marked failed`: both mean a purchase needs
manual attention (a refund Accounts refused, or a request that could not be matched).

Merge order: game PR #3, then the engine PR, then re-lock `composer.lock` to the merged engine commit, then this
branch.

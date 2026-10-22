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
* `AIRBRAKE_TOKEN`: optional error reporting.

`CREDITS_*` and `SERVERLIST_*` are optional and off by default; the game works without them.

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

* The `postdeploy` script in [`app.json`](app.json) runs `php tools/setup.php` on every deploy. It is idempotent and
  only installs the schema into an empty database.
* The cron jobs (`cron/run.php` every minute, `cron/ai.php` every 5 minutes) are also defined in `app.json`
  and are installed by Dokku automatically.
* Check a deployment with `tools/smoke.sh https://YOUR_DOMAIN`.

## OpenID Connect
* Redirect URL: https://YOUR_DOMAIN/oauth2/login/next

## CatLab Credits
Paid features used to run on the CatLab Credits framework. That service is not currently available, so leave
`CREDITS_*` unset.

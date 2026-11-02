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

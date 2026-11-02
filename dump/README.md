# Database schema and migrations

## Files

- **`dump.sql`**: the authoritative schema. `tools/setup.php` installs it into an empty database (local setup
  and the Dokku postdeploy script) and then records every migration as applied, because the dump already
  contains all of them.
- **`migrations/upgrade-YYYYMMDD[-name].sql`**: migrations, one file per change. The date comes first so the
  files sort in order; an optional `b`, `c` or `-short-name` suffix keeps same-day files apart
  (`upgrade-20261102b.sql`, `upgrade-20261102-npc-tribes.sql`). Never delete or edit one after it has been
  applied: developers and production bring their databases up to date from these files.
- The **`migrations`** table records which files have run. `tools/migrate.php` creates it when missing.

## Changing the schema

1. Add `migrations/upgrade-YYYYMMDD-name.sql` with the change **and apply the same change to `dump.sql` in the
   same commit**, so fresh installs and the next snapshot match. Keep `;` out of comments: full-line `--` comments
   are stripped, but statements are otherwise split on every `;`. Prefer plain `CREATE TABLE` / `ALTER TABLE`:
   a file runs exactly once per database.
2. Locally: `docker compose exec web php tools/migrate.php`.
3. On deploy, Dokku runs `php tools/migrate.php` as the predeploy script (`app.json`) in the new image before it
   takes traffic. Every file not yet in the `migrations` table is executed and recorded, in file name order. A
   failing statement exits 1 and aborts the deploy; the old release keeps serving.
4. Migrations run *before* the new code is live, while the old code is still serving. A change the old code
   cannot cope with (dropping or renaming a column it uses) needs two deploys: code first, then the migration.
5. A migration that failed halfway (MySQL commits every DDL statement immediately) must be finished or reverted
   by hand before the next deploy, since the file is run again from the top.

Never commit production data. `dump.sql` holds structure only (plus the handful of seed rows the game needs).

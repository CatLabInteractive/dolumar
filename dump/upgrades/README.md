# Schema upgrades

Every `*.sql` file in this directory runs, in name order, on every deploy
(`tools/setup.php`) when the database is already installed. Each file must
therefore be safe to run again (`CREATE TABLE IF NOT EXISTS`, guarded
`ALTER`s, ...). Any error fails the deploy.

Every schema change goes in a file here *and* in `dump/dump.sql`, so fresh
installs and existing databases end up identical.

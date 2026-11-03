# Schema upgrades

Every `*.sql` file in this directory runs, in name order, on every deploy
(`tools/setup.php`, from the Dokku `predeploy` hook) when the database is already installed. Each file must
therefore be safe to run again (`CREATE TABLE IF NOT EXISTS`, guarded
`ALTER`s, ...). Any error aborts the deploy before the new release starts.

Every schema change goes in a file here *and* in `dump/dump.sql`, so fresh
installs and existing databases end up identical.

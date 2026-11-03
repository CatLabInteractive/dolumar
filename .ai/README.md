# AI documentation for Dolumar

This directory holds documentation written for AI coding agents (and useful to humans too). The root
[`AGENTS.md`](../AGENTS.md) is the entry point and points here.

| File | Read it when |
|------|--------------|
| [architecture.md](architecture.md) | You need to understand how a request is handled, or where a feature lives. |
| [development.md](development.md) | You want to run the game locally, run tests, or lint. |
| [conventions.md](conventions.md) | You are about to write or change code. |
| [deployment.md](deployment.md) | You touch configuration, cron, `app.json`, `Procfile` or anything deploy-related. |
| [repositories.md](repositories.md) | A change needs the engine, or you wonder what `dolumar-portal` is. |
| [superpowers/](superpowers/) | Background on past large changes (the PHP 8 + Dokku migration). |

## Keeping these docs current

When a change makes something in here wrong (a new env var, a moved directory, a new test command), update the
relevant file in the same pull request. Keep facts here that cannot be read quickly from the code; do not copy code.

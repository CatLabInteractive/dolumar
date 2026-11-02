# The Dolumar repositories

| Repository | What it is | Language / stack | Deployed as |
|------------|------------|------------------|-------------|
| [`dolumar`](https://github.com/CatLabInteractive/dolumar) (this one) | The game. | PHP 8.4, MySQL, Memcached | Dokku app, game.dolumar.com |
| [`dolumar-engine`](https://github.com/CatLabInteractive/dolumar-engine) | "Neuron" game-server framework the game runs on. A Composer library, not runnable on its own. | PHP 8.4 | Composer dependency of `dolumar` |
| [`dolumar-portal`](https://github.com/CatLabInteractive/dolumar-portal) | Marketing / portal website (www.dolumar.com), built on the private `catlab-ancient-cms`. Shares no code with the game. | PHP 8.5, MySQL | Separate Docker/Dokku app |

Each repository has its own `AGENT.md` and `.ai/` directory.

## Changing the engine

1. Make the change in `dolumar-engine` on a feature branch. With the local Docker stack the engine checkout is
   mounted over `vendor/`, so you can test it in the game straight away.
2. Run the engine tests: `docker compose exec -w /engine web vendor/bin/phpunit`.
3. Open a PR in `dolumar-engine`.
4. `dolumar`'s `composer.json` requires the engine as `dev-php8` (the `php8` branch). After the engine PR is merged,
   make sure the change is on the branch that `composer.json` points to, then run
   `composer update catlabinteractive/dolumar-engine` here and commit the updated `composer.lock` in a `dolumar` PR.
   Production only gets engine changes through that lock file.

Note: `master` of the engine already contains the `php8` work. Moving `composer.json` to a tag or to `dev-master`
is a reasonable cleanup, but it is a deliberate change: do it in its own PR.

## Where does a change belong?

- Game rules, buildings, units, races, game windows and pages, game templates and translations: `dolumar`.
- Routing, sessions, login/OIDC, DB layer, caching, mail, chat, forum, map renderer, generic windows, admin tools
  shared by any game: `dolumar-engine`.
- Website content, the public homepage theme: `dolumar-portal`.

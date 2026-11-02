# Architecture

## Two layers: game and engine

| Layer | Repository | Class prefix | Location at runtime |
|-------|------------|--------------|---------------------|
| Game | `dolumar` (this repo) | `Dolumar_*` | `src/Dolumar/` |
| Engine ("Neuron" game-server framework) | `dolumar-engine` | `Neuron_*`, plus legacy `BBGS_*`, `BrowserGamesHub_*`, `OAuth*` | `vendor/catlabinteractive/dolumar-engine/src/` |

Both use PEAR-style class names (`Dolumar_Windows_Economy` lives in `src/Dolumar/Windows/Economy.php`) and are
loaded through Composer's **classmap** autoloader. There are no namespaces. After adding a class, run
`composer dump-autoload` or it will not be found.

The engine provides the server loop, routing, sessions, login (OpenID Connect), DB access, caching, templates,
translations, chat, forum, the map renderer and generic windows. The game plugs into it through
`Dolumar_Game`, which implements `Neuron_GameServer_Interfaces_Game`.

## Request flow

1. `public/.htaccess` rewrites every path that is not a real file to `public/dispatch.php?module=<path>`.
2. `public/dispatch.php` strips a leading slash and includes `public/index.php`.
3. `public/index.php` loads `bootstrap/bootstrap.php`, creates `Dolumar_Game`, and calls
   `Neuron_GameServer::bootstrap()->setGame($game)->dispatch()` (engine: `src/Neuron/GameServer.php`).
4. `dispatch()` switches on the first path segment of `module`:
   - `''` (home): the game shell page with the initial windows from `Dolumar_Game::getInitialWindows()`.
   - `dialog`: AJAX calls from the client that open, update or post to windows. Responses are XML.
   - `map`, `image`: map tiles and generated images.
   - `page`: full pages, resolved by `Dolumar_Game::getPage()` to `Dolumar_Pages_*`.
   - `admin`: moderator and admin pages (engine `Neuron_GameServer_Pages_Admin_*` and game `Dolumar_Pages_Admin_*`).
   - `api`, `openid`, `oauth2/login`, `oauth2/login/next` (OIDC callback), `gameserver/assets/*` (engine assets),
     `time`, `test` (locked down).
5. `public/underworld.php` is a separate entry point for the Underworld mini-game (`src/Dolumar/Underworld/`).

## Bootstrap and configuration

`bootstrap/bootstrap.php` loads Composer, reads `.env` through phpdotenv (with a putenv adapter, because the code
reads settings with `getenv()`), then includes `bootstrap/serverconfig.php` if present or
`bootstrap/serverconfig-default.php`. Configuration ends up as **global PHP constants** (`ABSOLUTE_URL`,
`DB_SERVER`, `SPEED_FACTOR`, `TEMPLATE_DIR`, ...), almost all wrapped in `if (!defined(...))` so that an earlier
definition wins. The engine's `src/connect.php` defines more constants, including `NOW` (request time).

## The UI model: windows

The client (`public/dolumar/javascript/`, plus engine assets) is a desktop-like UI of draggable windows on top of
an isometric map. Each window is a PHP class:

- Game windows: `src/Dolumar/Windows/*.php`, class `Dolumar_Windows_<Name>`, found by
  `Dolumar_Game::getWindow()`. If no game class matches, the engine looks in its own `Neuron_GameServer_Windows_*`.
- Every window extends `Neuron_GameServer_Windows_Window`. The usual methods to override are
  `setSettings()` (size, title, flags such as `setAllowOnlyOnce()`), `getContent()` (returns HTML), and
  `processInput()`/`getRefresh()` for posted data and polling. Request parameters come from
  `$this->getRequestData()` and `$this->getInputData()`.
- `getContent()` usually fills a `Neuron_Core_Template` and returns `$page->parse('name.tpl')`.

## Domain model (game)

Under `src/Dolumar/`:

- `Players/` (`Dolumar_Players_Player`, `Dolumar_Players_Village` and the per-village managers in `Players/Village/`
  for resources, buildings, units, honour, ...) and `Players/Server.php`.
- `Buildings/`, `Units/`, `SpecialUnits/`, `Technology/`, `Effects/` (spells and boosts), `Races/`, `Battle/`,
  `Quests/`, `Report/`, `Logable/` (game log entries).
- `Map/` (old map logic, still the source of truth for locations) and `NewMap/` (adapter to the engine's map
  renderer). `Dolumar_Game::getMap()` wraps the old map in the new one.
- `Underworld/`: a separate, turn-based mini-game with its own models, mappers and windows.
- `Mappers/` and `Underworld/Mappers/`: the few classes that follow a mapper pattern. Most models query the database
  directly.

Game data that is not code lives in `resources/`: `templates/default/` (views), `language/{en,fr,de}/*.lng`
(translations), `stats/` (unit and building stats per race), `maps/`.

## Background work

There is no queue worker. Time-based progress (building, training, battles, AI) is computed lazily on access and by
cron:

- `cron/run.php`, every minute: runs `cron/constantly.php` (premium queues and periodic processing) and
  `cron/daily.php` under `Neuron_Core_Lock` locks so they never overlap.
- `cron/ai.php`, every 5 minutes: the NPC tribes (below).

Cron and setup scripts refuse to run outside the CLI.

## NPC tribes

Every player gets a goblin village (owned by `Dolumar_Players_NPCPlayer`, player id 0) within 50 tiles. The tribe
code lives in `src/Dolumar/Npc/` and is driven by `Dolumar_Players_Village_Ai::run()` from `cron/ai.php`, under a
`npc_ai` lock. One run per village is: `Dolumar_Npc_SnapshotBuilder` (reads the village, new `battle_report` and
`villages_transfers` rows, the neighbours) → `Dolumar_Npc_StandingUpdater` (pure: events to standing rows and
transitions) → the planners `Dolumar_Npc_Planner_Economy`, `_Army`, `_War` (pure: snapshot to
`Dolumar_Npc_Action`s) → `Dolumar_Npc_Executor` (the only writer: build, train, squads, `attackVillage()`, log
notifications) → `Dolumar_Npc_StandingRepository` saves the rows. The decision classes never touch the database or
`NOW`, which is what the unit tests in `tests/Npc/` cover.

Each tribe keeps a hidden standing per player (-100..100) that decays toward 0 and drives its behaviour: raided
tribes send scouts, an envoy with a price, then war parties; gifts and delivered requests make them friendly
enough to send support squads. Every number is a constant in `Dolumar_Npc_Config`; `NPC_AGGRESSION`
(`off`/`counter`/`full`) and `NPC_PROTECTION_RUNES` come from the environment. Tables `npc_villages`,
`npc_standings` and `npc_log` come from the migration `dump/migrations/upgrade-20261102-npc-tribes.sql`. The full
design is `.ai/superpowers/specs/2026-11-02-npc-standing-ai-design.md`.

## Storage

- MySQL: the whole game state. The schema is `dump/dump.sql`; `tools/setup.php` installs it only into an empty
  database. Schema changes are migration files in `dump/migrations/` (`upgrade-YYYYMMDD-name.sql`), run once per
  database by `tools/migrate.php` (the Dokku predeploy script) and recorded in the `migrations` table; the same
  change goes into `dump/dump.sql` in the same commit. See `dump/README.md`.
- Memcached: required. The map, sessions and many lookups depend on it.
- Filesystem: custom sign images in `SIGNS_PATH` (persistent volume in production) and a cache in `CACHE_DIR`.

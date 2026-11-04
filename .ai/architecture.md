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

### Layout modes

The client runs in one of two layout modes, `desktop` or `mobile`, shown as `body.layout-desktop` or
`body.layout-mobile`. The player picks a preference (`desktop`, the default, `mobile` or `auto`) in MyAccount; it is
stored as the player preference `layout`, and `?layout=desktop|mobile|auto` on the shell URL overrides it for one
page load. `auto` means mobile below a viewport width of 800 CSS pixels. In mobile mode every `window`-type dialog
opens as a full-screen sheet in a stack (`#sheets`, back and close buttons) instead of a draggable window; the PHP
windows and the dialog XML are the same in both modes. `panel` windows (Menu, Statusbar, Newsbar, MiniMap, Guide)
stay panels and are restyled by CSS. Switching mode moves open windows between the two forms.

On a phone the Statusbar, Newsbar, MiniMap and Guide panels are hidden; their content still has to reach the player:
the Newsbar feeds the header (village, resources, inbox) and newsflashes scroll over the resource strip
(`Game.gui.newsflashHost`), the Statusbar's counters and links are copied into the ticker row and its sheet (the copy
keeps the Statusbar's `dialogId`, so cancel and withdraw post to that window), the Guide shows itself as a card above the tab
bar when it renders new advice (the server marks advice read when it renders it) and folds to a one-line task chip
while a guide quest is open, and the Map button shows the MiniMap. The header and
tab bar lie above `#sheets`: while a confirm is up they do nothing, and while a building is being placed a tap on them
cancels the placement first.

- Engine: `assets/javascript/layout.js` (`Game.layout`: mode, preference, breakpoint, `onChange`, the `meta#viewport`
  of the shell), `assets/javascript/sheets.js` and `assets/css/sheets.css` (`Game.sheets`), the sheet branch in
  `gui.js`, map scale, pinch zoom and touch placement in `scrollmap.js`/`gamemap.js`, and the preference toggle in
  MyAccount (`templates/gameserver/account/myAccount.tpl`).
- Game: `public/dolumar/css/mobile.css` (everything scoped under `body.layout-mobile`) and `Game.dolumar.mobile` in
  `public/dolumar/javascript/dolumar.js`, which builds the mobile header, the ticker and the bottom tab bar from the
  Newsbar, Statusbar and Menu panels. `tools/mobile-smoke.js` is the browser smoke test for both modes.

## Domain model (game)

Under `src/Dolumar/`:

- `Players/` (`Dolumar_Players_Player`, `Dolumar_Players_Village` and the per-village managers in `Players/Village/`
  for resources, buildings, units, honour, ...) and `Players/Server.php`.
- `Buildings/`, `Units/`, `SpecialUnits/`, `Technology/`, `Effects/` (spells and boosts), `Races/`, `Battle/`,
  `Quests/`, `Report/`, `Logable/` (game log entries).
- The guide: `Dolumar_Guide` starts the quest chain on registration (`Quests/S1BuildFarm` to `S10JoinClan`: five
  buildings, train, scout runes, fill a regiment, attack the nearest goblins, the battle, a clan). Each step extends
  `Dolumar_Quests_Quest`, adds one guide message (`resources/language/*/templates/guide/`) and names an objective
  and a "Show me" action (`guide.lng`, `[objectives]`). `Dolumar_Windows_Guide` polls every 10 s and evaluates the
  quests, so steps that are not buildings advance without a page load. While a new player (under 7 days) has an open
  quest the game skips the Welcome window, and race selection closes My Account instead of showing it.
- `Map/` (old map logic, still the source of truth for locations) and `NewMap/` (adapter to the engine's map
  renderer). `Dolumar_Game::getMap()` wraps the old map in the new one. `NewMap/` also holds the moving armies shown
  on the map: `MovingRows` (SQL for in-flight `battle` and `squad_commands` rows, the viewer's related villages, a
  20 s memcache of the rows), `ArmyFactory` (turns a row into a `Dolumar_NewMap_Army` with one engine `Movement` per
  phase: march out, wait at the target, march home; portal routes jump), and
  `MapObjectManager::getMovingObjects()`, which the engine's `map/objects` endpoint calls. Routes are stored at
  dispatch time in `battle.route` and `squad_commands.sc_route` (JSON waypoints from `Dolumar_Map_Distance`).
  Related viewers (own or clan villages involved) get the full path and a tap action; everyone else sees a neutral
  sprite with only the next 30 s of movement. The kind texts come from `resources/language/*/map.lng` through
  `CONFIG_MAPOBJECT_TEXT` in `gameserver/index/header.phpt`; the engine layer itself is generic (`Game.mapObjects`, `mapobjects.js`).
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

## Browser notifications

Web Push is built into the engine (see the engine's `.ai/architecture.md`): subscriptions, the queue, the `sw.js`
and `push/*` routes and the "Browser notifications" section of My Account. The game supplies:

- Messages: the `notifications` language file. Every non-public `sendNotification()` (incoming attack, resources,
  runes or equipment received, clan portals) also becomes a push.
- Timed events: `Dolumar_Push_Events::scan()` runs every minute from `cron/push.php` (called by `cron/run.php`) and
  notifies about buildings finished or upgraded (`map_buildings.readyDate` / `lastUpgradeDate`), battles fought
  (`battle.fightDate`, withdrawn attacks excluded) and armies home (`battle.endDate`) since the watermark
  `push_scan` in `n_server_data`. Because `removeBattle()` deletes the row, it also calls
  `Dolumar_Push_Events::onBattleRemoved()` for armies that come home before the next scan. `cron/push.php` then
  delivers the queue.
- Categories: `Dolumar_Players_Player::getPushCategories()` (sections of `notifications.lng`, labels in its
  `[categories]` section) and `getPushDefaultMuted()` (`portal`).
- `public/manifest.webmanifest` and the icons, linked from `gameserver/index/header.phpt`. iOS only allows push for
  a site added to the home screen.

All of it is off until `VAPID_PUBLIC_KEY` and `VAPID_PRIVATE_KEY` are set.

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
`npc_standings` and `npc_log` are created by `dump/upgrades/20261102-npc-tribes.sql`. Moderators see every tribe,
its standings and its decision log (`npc_log`) on the admin page "NPC tribes" (`Dolumar_Pages_Admin_Npctribes`,
`/admin/npctribes`, filters `?vid=` and `?plid=`). The full
design is `.ai/superpowers/specs/2026-11-02-npc-standing-ai-design.md`.

## Storage

- MySQL: the whole game state. The schema is `dump/dump.sql`; `tools/setup.php` installs it only into an empty
  database and otherwise runs every file in `dump/upgrades/` (`YYYYMMDD-name.sql`) on each deploy, so those files
  must be idempotent (`CREATE TABLE IF NOT EXISTS`, guarded `ALTER`s). The same change goes into `dump/dump.sql`
  in the same commit. See `dump/upgrades/README.md`.
- Memcached: required. The map, sessions and many lookups depend on it.
- Filesystem: custom sign images in `SIGNS_PATH` (persistent volume in production) and a cache in `CACHE_DIR`.

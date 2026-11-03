# Dolumar: moving armies on the map — design

Date: 2026-11-02
Status: approved in conversation (Thijs, 2026-11-02); implementation plan follows

## Intent and why

Players send armies to attack other villages and move squads between villages, but the map never shows any of it:
an army exists only as a line in the Battle window and a notification. Thijs wants marching armies drawn on the
isometric map, moving in real time, so the map feels alive and shows what is going on.

Decisions taken with Thijs (2026-11-02):

1. **Everyone sees every army** on the map, but **only related players get information** about it. Related means
   the viewer, or a member of one of the viewer's clans, owns the attacking village or the attacked village (for a
   squad move: the squad's home village or its destination). Unrelated viewers see a sprite move and nothing else,
   and the server response must not leak origin, destination, route or kind either.
2. The server side is built on the engine's dormant movement model (`Neuron_GameServer_Map_MapObject` with
   `Neuron_GameServer_Map_Movement` paths), exported through a reworked `map/objects` endpoint. The client gets a new
   animated layer; nothing in the browser consumes that endpoint today.
3. All movement kinds are shown: attack marching out, waiting or fighting at the target, marching home, and squad
   moves (including recalls). Routes through portals are drawn as a jump, not a glide.
4. Tapping a related army shows who, where, which phase and the ETA, and opens the Battle or Squads window. Unrelated
   armies are not tappable.
5. Desktop map first; minimap dots are a follow-up. Anonymous visitors get no armies (endpoint is logged-in only).

Success = a logged-in player watches their attack leave their village, wait at the target, and march back, at the
right place at the right time, on desktop and on a phone, and another player who is not involved sees only an
anonymous army sprite moving.

## Non-goals

- No change to travel times, battle resolution, honour or any game rule. The timestamps in `battle` and
  `squad_commands` stay the single source of truth.
- No minimap integration, no sound, no "cancel attack" from the map.
- No change to how regions are rendered or cached. Armies are a separate layer.
- No rewrite of the Prototype-era client. The new JavaScript follows the style of `gamemap.js`.
- No change to the responsive-shell work in the parallel thread; that work decides how an opened window is shown on a
  phone, this work only calls `openWindow`.

## Background (what exists today)

- Attacks: table `battle` (`vid` attacker, `targetId`, `startDate`, `arriveDate`, `fightDate`, `endFightDate`,
  `endDate`, `goHomeDuration`, `isFought`), created in `Dolumar_Players_Village_Battle::attackVillage()`. Battles are
  processed lazily by `processBattles()` when either village loads; `endDate` is only known after the fight, and a
  fight can be delayed by earlier battles on the same target (`delayFightDate`). A wiped-out army has
  `endDate = endFightDate + 1` and never returns.
- Squad moves: table `squad_commands` (`s_id`, `s_action = 'move'`, `s_start`, `s_end` as DATETIME, `s_from`, `s_to`
  village ids), created in `Dolumar_Players_Squad::sendToVillage()`. A recall deletes the row and inserts a new one.
- Distance: `Dolumar_Map_Map::getDistanceBetweenVillages()` runs an A* search over the two town centres and the
  players' portals (`Dolumar_Map_Distance::getShortestDistance()`), which builds the full route as waypoints but
  returns only the distance.
- Village position: the town centre building's `map_buildings.xas` / `yas`.
- Map client: engine `assets/javascript/scrollmap.js` (`TiledImageViewer`, one scrolling div with touch and mouse
  panning) and `gamemap.js` (regions of 10 × 10 tiles, fetched through `map/region/`, drawn on one canvas per region
  with an image-map overlay for clicks). `Game.map.getOrthogonalCors(gx, gy)` converts a tile position to pixels.
  Regions are memcached and all-or-nothing, so armies must not be drawn into them.
- Engine movement model: `Neuron_GameServer_Map_Movement` (2 to 4 points, start and end timestamp,
  `getCurrentLocation($time)`, `isActive($time)`, `getData()`), `Neuron_GameServer_Map_MapObject::addMovement()` /
  `getLocation($time)` / `getExportData()`, and `scripts/map/objects.php` (route `map/objects`), which ignores the
  posted regions, loads one radius-100000 bubble and answers XML. Nothing calls it.

## Design

### 1. Data: store the route at dispatch time

Two nullable JSON columns, written when a movement is created and never updated:

| Table | Column | Content |
|-------|--------|---------|
| `battle` | `route` TEXT NULL | JSON array of `[x, y]` tile waypoints from the attacker's town centre to the target's, e.g. `[[12,5],[20,9],[-3,41],[-7,44]]` (start, portal in, portal out, end). Two entries when there is no portal. |
| `squad_commands` | `sc_route` TEXT NULL | Same, from `s_from` to `s_to`. |

`Dolumar_Map_Map` gets `getRouteBetweenVillages($village1, $village2, $ignoreImpassable = true)` which returns
`array('distance' => float, 'route' => array of [x, y])` from the existing A* (the route is already built in
`getShortestDistance`; only the public return value changes, `getDistanceBetweenVillages()` keeps returning the
distance). `attackVillage()` and `sendToVillage()` call it once, use the distance exactly as before and store the
route. A route with more than two waypoints means a portal was used; consecutive waypoints that are a portal pair are
the jump.

Rows without a route (existing battles at deploy time) fall back to a straight line between the two town centres.

Schema change: the migration `dump/migrations/upgrade-20261102-army-routes.sql` (two `ALTER TABLE ... ADD COLUMN`
statements, run once by `tools/migrate.php` from the migration flow of PR #8) plus the columns in `dump/dump.sql`.

### 2. Server model: armies as engine map objects (game repo)

`Dolumar_NewMap_Army extends Neuron_GameServer_Map_MapObject`, built by a small factory from one `battle` row or one
`squad_commands` row joined to both town centres:

- Kind: `attack`, `waiting`, `returning`, `squad`.
- Phases become movements, each a straight two-point `Neuron_GameServer_Map_Movement` with absolute timestamps:
  - attack marching out: `startDate → arriveDate` along the route; a portal route is two movements (village to
    portal in, portal out to target) split pro rata by segment length, so the sprite jumps at the portal.
  - waiting / fighting: `arriveDate → endFightDate` (or, while the fight is overdue but not yet processed,
    `arriveDate → now + poll interval`) as a zero-length movement at the target.
  - marching home: `endFightDate → endDate` along the reversed route, only when `isFought = 1` and
    `endDate > endFightDate + 1`.
  - squad move: `s_start → s_end` along the route.
- `getLocation($time)` comes from the base class (interpolates the active movement). Before `startDate` and after the
  last movement the object sits at its start or end location.
- Display object: a `Neuron_GameServer_Map_Display_Sprite` per kind (`attack`, `returning`, `squad`, `waiting`,
  `neutral`), with a facing chosen by the client from the direction of travel.
- Click event (related tier only): `openWindow('battle', {'vid': <own village id>})` for attacks,
  `openWindow('squads', {'vid': <home village id>})` for squad moves.
- Name (related tier only): "<attacker> → <target>" or "<squad> → <village>".

`Dolumar_NewMap_MapObjectManager::getMovingObjects(array $areas, $viewer)` (new engine method, see 3) runs two
queries:

```
SELECT b.*, tc1.xas AS fromX, tc1.yas AS fromY, tc2.xas AS toX, tc2.yas AS toY, v1.plid AS fromPlid, v2.plid AS toPlid
FROM battle b
JOIN map_buildings tc1 ON tc1.village = b.vid AND tc1.buildingType IN (1,3) AND tc1.destroyDate = 0
JOIN map_buildings tc2 ON tc2.village = b.targetId AND tc2.buildingType IN (1,3) AND tc2.destroyDate = 0
JOIN villages v1 ON v1.vid = b.vid
JOIN villages v2 ON v2.vid = b.targetId
WHERE b.endDate > NOW_TS OR b.isFought = 0
```

and the equivalent on `squad_commands` with `s_end > FROM_UNIXTIME(NOW_TS)`. Rows are filtered in PHP to movements
whose bounding box (all waypoints, padded by one tile) overlaps any requested area. This is cheap: the live game has
a handful of battles at a time, and the result set is cached (see 4).

### 3. Engine: a region-aware JSON `map/objects` endpoint

In `dolumar-engine`:

- `Neuron_GameServer_Map_Managers_MapObjectManager::getMovingObjects(array $areas, $viewer = null)` with a default
  implementation that returns `array()`, so other games and the existing `getDisplayObjects()` are untouched.
- `scripts/map/objects.php` is rewritten: it reads `regions[]` with the same `Neuron_Core_Tools::sanitizeMapRegions()`
  and region-to-area maths as `jsonmap.php`, requires a logged-in player (`Neuron_GameServer::getPlayer()`, else
  HTTP 403 with an empty JSON list), calls `getMovingObjects()`, and answers JSON:

```
{
  "servertime": 1762108800,
  "poll": 30,
  "objects": [
    {
      "id": "b:123" | "a:9f2c...",
      "kind": "attack" | "waiting" | "returning" | "squad" | "neutral",
      "x": 12.4, "y": 5.1,                      // position at servertime
      "sprite": {"id": "...", "url": "..."},     // Sprite::getDisplayData()
      "name": "Castle Black → Winterfell",       // related only
      "click": "openWindow('battle', {...})",    // related only
      "paths": [ {"start": 1762108000, "end": 1762110000, "points": [[12,5],[20,9]]}, ... ]
    }
  ]
}
```

  `paths` comes from `Neuron_GameServer_Map_Movement::getPath()` and the timestamps; the 3D fields of
  `MapObject::getExportData()` (`up`, `defaultRotation`, `direction`, velocity) are not sent. The old XML output and
  the hard-coded bubble are removed (nothing uses them).
- `Neuron_GameServer_Map_Movement` gets a `truncate($from, $to)` helper returning a new two-point movement between
  `getCurrentLocation($from)` and `getCurrentLocation($to)`, used by the redaction in 4, and its `getData()` typo
  (`diration`) is fixed while tests are added.

### 4. Visibility tiers and caching

The related set is computed once per request: ids of the viewer's own villages plus the villages of every member of
every clan the viewer is in (`Player::getVillages()`, `Player::getClans()`, `Clan::getMembers()`). A movement is
related when `vid` or `targetId` (squads: `s_from`, `s_to`, or the squad's home village) is in the set.

| | Related | Unrelated |
|---|---|---|
| `id` | `b:<battleId>` / `s:<sc_id>` | `a:<sha1(secret + id)>` truncated to 12 chars; stable within a deploy so the client can update the sprite, not reversible |
| `paths` | every phase movement with real timestamps and waypoints | one movement from the position at `servertime` to the position at `servertime + poll`, built with `Movement::truncate()`; nothing before or after |
| `kind` | real kind | `neutral` |
| `name`, `click` | present | absent |
| `sprite` | the kind's sprite | the `neutral` sprite (`MapObject::getNeutralDisplayObject()`) |
| sent when | the route touches a viewed region | only while the current position is inside a viewed region |

`secret` is a per-deploy value derived from existing configuration (no new env var): `sha1(DB_PASSWORD . 'army')`
computed once per request. It only has to stop two players comparing ids across sessions, not resist an attacker.

Caching: the unredacted object list per request-region-set is memcached for 20 s under a key built from the sorted
region list (`Neuron_Core_Memcache::getInstance()`, like `Dolumar_Map_CacheMemcached`). Redaction is applied per
request after the cache, because it depends on the viewer. The cache is an optimisation only; with memcached down
the endpoint still answers.

### 5. Client: an animated moving-objects layer

New engine file `assets/javascript/mapobjects.js` (generic: the engine knows moving objects, not armies), loaded from `templates/gameserver/index.phpt` after `gamemap.js`,
exposing `Game.mapObjects`:

- **Layer.** `Game.mapObjects.init()` is called from `Game.map.onLoadImagesDone` after the `TiledImageViewer` exists. It
  appends one `div.mapobjects` to `Game.map.oScrollMap.div` (the element that pans), `position: absolute; left: 0;
  top: 0; z-index: 20; pointer-events: none`. Sprites are child `div.mapobject` elements with `pointer-events: auto`,
  a background image from `sprite.url`, a `title`, and a 40 × 40 px hit area centred on the sprite.
- **Polling.** After every `Game.map.drawMapRegionCommit` and every `poll` seconds, POST the list of loaded regions
  (`Game.map.oScrollMap.loadedMapTiles` keys) to `CONFIG_DISPATCH_URL + 'map/objects/'`. The response replaces the
  internal map `{id → object}`: new ids get a sprite, known ids get updated paths, missing ids are removed. The clock
  offset is `servertime - Date.now()/1000`, refreshed on every response.
- **Animation.** A single `setInterval` of 1000 ms (no work when no sprite is on screen) computes for each object
  the active path at `now + offset`, the fraction elapsed, interpolates `[x, y]` between the two points, converts with
  `Game.map.getOrthogonalCors(x * -1, y)` (same sign convention as `mapIsoJump`), and sets `left` / `top`. Before the
  first path it uses the first point, after the last path the last point. The facing class (`left`/`right`) is set
  from the sign of the pixel delta since the last tick.
- **Route line.** For related objects, a dashed `<polyline>` of all path points is drawn in one `<svg>` in the
  layer (not a canvas: a bitmap sized to the bounding box of every route exceeds mobile canvas limits), redrawn
  when the object set changes. Unrelated objects get no line. The response lists objects sorted by exported id, so
  the order never reveals how the game grouped them.
- **Tap.** A click or tap on a sprite with a `click` command shows a small label (name, kind, ETA = end of the
  current path) next to it and a "Open" button that evaluates the command with the same `Game.map.onObjectClick`
  path used by buildings. Sprites without a command do nothing.
- **Mobile.** The layer works unchanged under the responsive shell: panning is handled by `scrollmap.js`, taps by the
  sprite element. No hover-only behaviour.

### 6. Sprites and text

Game repo: five small PNGs under `public/static/images/sprites/armies/` (`attack`, `returning`, `squad`, `waiting`,
`neutral`), each with a left and right facing frame side by side, served through the existing static URL. Labels use
new keys in `resources/language/{en,fr,de}/map.lng` (kind names and "Open").

## Error handling

- Endpoint errors (DB down, exception in the factory) answer an empty `objects` list with HTTP 200 and log the
  exception; the map keeps working without armies.
- A movement whose village lost its town centre (destroyed) is skipped.
- A route whose JSON fails to decode falls back to the straight line.
- Client: a failed poll keeps the last known objects and retries at the next interval; `servertime` missing means
  the response is ignored.

## Testing

Engine (`tests/`, PHPUnit, run through the game's Docker stack):

- `Map/MovementTest`: `getCurrentLocation()` at start, middle and end of a two-point movement; `isActive()`
  boundaries; `truncate()` returns the right two points and timestamps.
- `Map/MapObjectTest`: `getLocation($time)` picks the active movement among several, sits at start before the first
  and at end after the last.
- `GameServerMapObjectsRouteTest`: `map/objects` without a player answers 403 and `{"objects":[]}`; with a stub game
  whose manager returns one object it answers JSON with `servertime`, `poll` and the object.

Game (no PHPUnit suite exists; `.ai/development.md` says tests live in the engine):

- `Dolumar_NewMap_ArmyFactory` is written so its phase-to-movement logic is a pure function of the row and `NOW`,
  and gets a PHPUnit test in the engine-style under `tests/` **in the game repo** (new `phpunit.xml.dist`, bootstrap
  loading Composer only), covering: straight route, portal route split, overdue unprocessed fight, wiped-out army
  (no return), squad move, missing route fallback.
- Redaction gets a test: an unrelated viewer never receives `name`, `click`, real ids, or a path outside
  `[servertime, servertime + poll]`.
- `tools/smoke.sh` gains a request to `map/objects/` (expects 403 anonymous).
- Manual: in the Docker stack, dispatch an attack and a squad move, watch the sprites leave, wait, return; log in as
  an unrelated player and confirm only neutral sprites with short paths in the network response.

## Rollout

1. Engine PR (branch `claude/project-thread-ped4we`): manager method, endpoint rewrite, `Movement::truncate()`,
   `mapobjects.js`, template include, tests.
2. Game PR (same branch name): route columns and setup step, `getRouteBetweenVillages()`, army factory and map object,
   `getMovingObjects()`, sprites, language keys, `.ai/architecture.md` update (map section mentions the army layer and
   `map/objects`), composer.lock bump to the engine commit.
3. Deploy engine first (no behaviour change until the game calls it), then the game, after the migration flow
   (game PR #8) is in; its predeploy `tools/migrate.php` adds the columns. Existing battles show straight lines until
   they finish.

## Open questions

None blocking. Minimap dots and clan-wide "show me all my clan's armies" list are follow-ups.

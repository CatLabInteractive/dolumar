# Moving Armies on the Map Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Marching armies (attacks, returns, squad moves) are drawn and animated on the isometric map; related players see who and where, unrelated players see an anonymous sprite and learn nothing from the server response.

**Architecture:** The engine (`dolumar-engine`) gets a region-aware JSON `map/objects` endpoint that exports `Neuron_GameServer_Map_MapObject`s with their `Neuron_GameServer_Map_Movement` paths, redacting them per viewer, plus a new `armies.js` layer that polls it and interpolates sprite positions client-side. The game (`dolumar`) stores the A* route at dispatch time, builds `Dolumar_NewMap_Army` objects from `battle` and `squad_commands` rows, and decides relatedness from the viewer's own and clan villages.

**Tech Stack:** PHP 8.4, mysqli through `Neuron_DB_Database`, Memcached through `Neuron_Core_Memcache`, PHPUnit 11 (engine, and a new minimal suite in the game), Prototype-era JavaScript (`Ajax.Request`, `$()`), Docker Compose stack from `.ai/development.md`.

**Spec:** `.ai/superpowers/specs/2026-11-02-moving-armies-on-map-design.md`

## Global Constraints

- No change to travel times, battle resolution, honour or any game rule; `battle` and `squad_commands` timestamps stay the single source of truth.
- Unrelated viewers never receive `name`, `click`, a real id, a real kind, or any path point outside `[servertime, servertime + poll]`.
- `map/objects` requires a logged-in player: anonymous answers HTTP 403 with body `{"objects":[]}`.
- Poll interval is 30 s (`poll: 30`); unredacted result cache lifetime is 20 s; sprite hit area is 40 × 40 px.
- Code style per `.ai/conventions.md`: tabs, braces on their own line, space before call parentheses, GPL header on new files, no closing `?>` in new files, every request value through `intval()` or `$db->escape()`.
- Engine public API: only additive changes (`getMovingObjects()` default returns `array()`, `isRelatedTo()` default `true`), so other games keep working.
- Schema changes are an idempotent step in `tools/setup.php` and a matching line in `dump/dump.sql`. No destructive SQL.
- Branch `claude/project-thread-ped4we` in both repos; commit messages end with `Co-Authored-By: Claude Fable 5.1 <noreply@anthropic.com>`.

## Review Focus

1. **A battle whose fight is overdue but not yet processed** (`fightDate < now`, `isFought = 0`, because nobody loaded either village) must show the army waiting at the target, not vanish or march home. Pinned by Task 9's `testOverdueUnprocessedFightWaitsAtTarget`.
2. **An army that died entirely** (`isFought = 1`, `endDate = endFightDate + 1`) must not get a return movement. Pinned by Task 9's `testWipedOutArmyHasNoReturn`.
3. **A clan member of the defender** is related and sees the full attack; a player in no clan who is neither attacker nor defender is not. Pinned by Task 10's `testClanMemberOfDefenderIsRelated` and `testStrangerIsNotRelated`.
4. **A village without a town centre** (destroyed, or `map_buildings` row with `destroyDate > 0`) must be skipped without an exception taking the whole response down. Pinned by Task 9's `testMissingTownCentreGivesNull` and Task 4's `testExporterSkipsObjectsThatThrow`.
5. **Memcached unreachable** must degrade to uncached answers, not errors. Pinned by Task 10's `testCacheMissWithoutMemcacheStillAnswers`.

---

## Phase 1: Engine model and endpoint (`dolumar-engine`)

All engine commands run from a `dolumar` checkout with the engine at `../dolumar-engine`:
`docker compose exec -w /engine web vendor/bin/phpunit` and `docker compose exec -w /engine web vendor/bin/phpcs`.

### Task 1: `Movement::truncate()` and movement tests

**Files:**
- Modify: `src/Neuron/GameServer/Map/Movement.php`
- Test: `tests/Map/MovementTest.php` (new directory `tests/Map/`)

**Interfaces:**
- Produces: `Neuron_GameServer_Map_Movement::truncate(int $from, int $to): Neuron_GameServer_Map_Movement` — a new two-point movement from `getCurrentLocation($from)` to `getCurrentLocation($to)` with those timestamps, clamped to `[getStartTime(), getEndTime()]`.
- Produces: `Neuron_GameServer_Map_Movement::getPathData(): array` — `array('start' => int, 'end' => int, 'points' => array of [x, y])`, `x`/`y` as floats.

- [ ] **Step 1: Write the failing tests**

```php
class MovementTest extends TestCase
{
	private function movement(): Neuron_GameServer_Map_Movement
	{
		return new Neuron_GameServer_Map_Movement(
			1000, 1100,
			new Neuron_GameServer_Map_Location(0, 0),
			new Neuron_GameServer_Map_Location(10, 20)
		);
	}

	public function testCurrentLocationInterpolatesLinearly(): void
	{
		$m = $this->movement();
		$this->assertEquals([0.0, 0.0], [$m->getCurrentLocation(1000)->x(), $m->getCurrentLocation(1000)->y()]);
		$this->assertEqualsWithDelta(5.0, $m->getCurrentLocation(1050)->x(), 0.001);
		$this->assertEqualsWithDelta(10.0, $m->getCurrentLocation(1050)->y(), 0.001);
		$this->assertEqualsWithDelta(10.0, $m->getCurrentLocation(1100)->x(), 0.001);
	}

	public function testIsActiveBoundaries(): void
	{
		$m = $this->movement();
		$this->assertFalse($m->isActive(999));
		$this->assertTrue($m->isActive(1000));
		$this->assertTrue($m->isActive(1099));
		$this->assertFalse($m->isActive(1100));
	}

	public function testTruncateReturnsSegmentBetweenTimes(): void
	{
		$t = $this->movement()->truncate(1020, 1050);
		$this->assertSame(1020, $t->getStartTime());
		$this->assertSame(1050, $t->getEndTime());
		$this->assertEqualsWithDelta(2.0, $t->getStartLocation()->x(), 0.001);
		$this->assertEqualsWithDelta(5.0, $t->getEndLocation()->x(), 0.001);
	}

	public function testTruncateClampsToMovementBounds(): void
	{
		$t = $this->movement()->truncate(900, 1200);
		$this->assertSame(1000, $t->getStartTime());
		$this->assertSame(1100, $t->getEndTime());
	}

	public function testPathDataIsPlainArray(): void
	{
		$this->assertSame(
			['start' => 1000, 'end' => 1100, 'points' => [[0.0, 0.0], [10.0, 20.0]]],
			$this->movement()->getPathData()
		);
	}
}
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `docker compose exec -w /engine web vendor/bin/phpunit tests/Map/MovementTest.php`
Expected: FAIL, `Call to undefined method ... truncate()` (the first three may already pass).

- [ ] **Step 3: Implement `truncate()` and `getPathData()` in `Movement.php`; rename the `diration` key in `getData()` to `direction`**

`truncate()` clamps with `max()`/`min()`, then `return new self ($from, $to, $this->getCurrentLocation ($from), $this->getCurrentLocation ($to));`. `getPathData()` maps `$this->locations` to `array ((float) $v->x (), (float) $v->y ())`.

- [ ] **Step 4: Run tests to verify they pass**

Run: `docker compose exec -w /engine web vendor/bin/phpunit tests/Map/MovementTest.php`
Expected: 5 tests, PASS. Then `vendor/bin/phpunit` (whole suite) PASS and `vendor/bin/phpcs` clean.

- [ ] **Step 5: Commit**

```bash
git add src/Neuron/GameServer/Map/Movement.php tests/Map/MovementTest.php
git commit -m "Add Movement::truncate() and getPathData() with tests"
```

### Task 2: `MapObject` relatedness and movement export

**Files:**
- Modify: `src/Neuron/GameServer/Map/MapObject.php`
- Test: `tests/Map/MapObjectTest.php`

**Interfaces:**
- Produces: `Neuron_GameServer_Map_MapObject::isRelatedTo($viewer): bool` — default `true`; games override.
- Produces: `Neuron_GameServer_Map_MapObject::getMovements(): array` of `Neuron_GameServer_Map_Movement`, in insertion order.
- Produces: `Neuron_GameServer_Map_MapObject::getKind(): string` — default `'object'`; games override.
- Existing, relied on later: `addMovement()`, `getLocation($time)`, `getDisplayObject()`, `getName()`, `getUOID()`, `getEvents('click')`.

- [ ] **Step 1: Write the failing tests**

```php
class MapObjectTest extends TestCase
{
	private function object(): Neuron_GameServer_Map_MapObject
	{
		$o = new class extends Neuron_GameServer_Map_MapObject {
			public function getDisplayObject () { return new Neuron_GameServer_Map_Display_Sprite ('x.png'); }
			public function getName () { return 'test'; }
		};
		$o->setLocation(new Neuron_GameServer_Map_Location(0, 0));
		$o->addMovement(new Neuron_GameServer_Map_Movement(1000, 1100,
			new Neuron_GameServer_Map_Location(0, 0), new Neuron_GameServer_Map_Location(10, 0)));
		$o->addMovement(new Neuron_GameServer_Map_Movement(1100, 1200,
			new Neuron_GameServer_Map_Location(10, 0), new Neuron_GameServer_Map_Location(10, 10)));
		return $o;
	}

	public function testLocationFollowsActiveMovement(): void
	{
		$o = $this->object();
		$this->assertEqualsWithDelta(5.0, $o->getLocation(1050)->x(), 0.001);
		$this->assertEqualsWithDelta(5.0, $o->getLocation(1150)->y(), 0.001);
	}

	public function testLocationBeforeFirstMovementIsStart(): void
	{
		$this->assertEquals(0.0, $this->object()->getLocation(500)->x());
	}

	public function testLocationAfterLastMovementIsEnd(): void
	{
		$l = $this->object()->getLocation(5000);
		$this->assertEqualsWithDelta(10.0, $l->x(), 0.001);
		$this->assertEqualsWithDelta(10.0, $l->y(), 0.001);
	}

	public function testDefaultsAreRelatedAndKindObject(): void
	{
		$this->assertTrue($this->object()->isRelatedTo(null));
		$this->assertSame('object', $this->object()->getKind());
		$this->assertCount(2, $this->object()->getMovements());
	}
}
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `docker compose exec -w /engine web vendor/bin/phpunit tests/Map/MapObjectTest.php`
Expected: FAIL on `isRelatedTo` undefined, and `testLocationAfterLastMovementIsEnd` fails (today `getLocation()` falls back to the static location, not the last end point).

- [ ] **Step 3: Implement in `MapObject.php`**

Add `isRelatedTo ($viewer)` returning `true`, `getKind ()` returning `'object'`, `getMovements ()` returning `$this->movements`. Change `getLocation ($time = NOW)`: if a movement is active return its current location; else if any movement has `getEndTime () <= $time`, return the end location of the one with the largest end time; else return `$this->location`.

- [ ] **Step 4: Run tests to verify they pass**

Run: `docker compose exec -w /engine web vendor/bin/phpunit tests/Map/`
Expected: 9 tests PASS.

- [ ] **Step 5: Commit**

```bash
git add src/Neuron/GameServer/Map/MapObject.php tests/Map/MapObjectTest.php
git commit -m "MapObject: relatedness, kind, movements accessor, rest at last movement end"
```

### Task 3: Manager hook and region-to-area helper

**Files:**
- Modify: `src/Neuron/GameServer/Map/Managers/MapObjectManager.php`
- Create: `src/Neuron/GameServer/Map/Regions.php`
- Test: `tests/Map/RegionsTest.php`

**Interfaces:**
- Produces: `Neuron_GameServer_Map_Managers_MapObjectManager::getMovingObjects(array $areas, $viewer = null): array` — default `array()`.
- Produces: `Neuron_GameServer_Map_Regions::toAreas(array $regions, int $tilesPerRegion): array` of `Neuron_GameServer_Map_Area`, using the same maths as `scripts/map/jsonmap.php` (`$switchpoint = $tilesPerRegion`; centre `(($x + $y) * $switchpoint) + ($switchpoint / 2)`, `(($x - $y) * $switchpoint) - ($switchpoint / 2)`; radius `$switchpoint * 2`).
- Produces: `Neuron_GameServer_Map_Regions::cacheKey(array $regions, int $tilesPerRegion): string` — `'movingobjects:' . $tilesPerRegion . ':' . implode (';', sorted "x,y" pairs)`.

- [ ] **Step 1: Write the failing tests**

```php
class RegionsTest extends TestCase
{
	public function testRegionZeroMapsToJsonmapCentre(): void
	{
		$areas = Neuron_GameServer_Map_Regions::toAreas([[0, 0]], 10);
		$this->assertCount(1, $areas);
		$this->assertEquals(5.0, $areas[0]->getCenter()->x());
		$this->assertEquals(-5.0, $areas[0]->getCenter()->y());
		$this->assertEquals(20, $areas[0]->getRadius());
	}

	public function testRegionOneZero(): void
	{
		$areas = Neuron_GameServer_Map_Regions::toAreas([[1, 0]], 10);
		$this->assertEquals(15.0, $areas[0]->getCenter()->x());
		$this->assertEquals(5.0, $areas[0]->getCenter()->y());
	}

	public function testCacheKeyIsOrderIndependent(): void
	{
		$a = Neuron_GameServer_Map_Regions::cacheKey([[1, 0], [0, 0]], 10);
		$b = Neuron_GameServer_Map_Regions::cacheKey([[0, 0], [1, 0]], 10);
		$this->assertSame($a, $b);
		$this->assertSame('movingobjects:10:0,0;1,0', $a);
	}
}
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `docker compose exec -w /engine web vendor/bin/phpunit tests/Map/RegionsTest.php`
Expected: FAIL, class not found (run `composer dump-autoload` in `/engine` after creating the file, classmap autoloader).

- [ ] **Step 3: Implement `Regions.php` and add `getMovingObjects()` to the manager**

- [ ] **Step 4: Run tests to verify they pass**

Run: `docker compose exec -w /engine web sh -c 'composer dump-autoload && vendor/bin/phpunit tests/Map/'`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add src/Neuron/GameServer/Map/Regions.php src/Neuron/GameServer/Map/Managers/MapObjectManager.php tests/Map/RegionsTest.php
git commit -m "Add getMovingObjects() hook and region-to-area helper"
```

### Task 4: Exporter with per-viewer redaction

**Files:**
- Create: `src/Neuron/GameServer/Map/MovingObjectsExporter.php`
- Test: `tests/Map/MovingObjectsExporterTest.php`

**Interfaces:**
- Consumes: Task 1 `truncate()`, `getPathData()`; Task 2 `isRelatedTo()`, `getKind()`, `getMovements()`, `getLocation()`.
- Produces: `new Neuron_GameServer_Map_MovingObjectsExporter(string $secret, int $poll = 30)`.
- Produces: `->export(array $objects, $viewer, int $now): array` with keys `servertime` (int), `poll` (int), `objects` (list). Each object: `id`, `kind`, `x`, `y`, `sprite` (from `getDisplayObject()->getDisplayData()`), `paths` (list of `getPathData()`), and for related only `name` and `click` (the `click` events joined with `' '`).
- Produces: `->anonymousId($uoid): string` — `'a:' . substr (sha1 ($this->secret . $uoid), 0, 12)`.

Redaction for `isRelatedTo($viewer) === false`: `id` = `anonymousId(getUOID())`, `kind` = `'neutral'`, `paths` = one entry built from the active movement at `$now` truncated to `[$now, $now + $poll]`, or an empty list when no movement is active (then `x`/`y` are `getLocation($now)` and the sprite just sits); no `name`, no `click`.

- [ ] **Step 1: Write the failing tests**

Use a small test double `ExportableObject extends Neuron_GameServer_Map_MapObject` with constructor `($uoid, $related, $kind, $name, $click)` that overrides `getUOID()`, `isRelatedTo()`, `getKind()`, `getName()`, `getDisplayObject()` (`Sprite('army.png')`) and registers `observe('click', $click)` when given. Movements as in `MapObjectTest::object()` (1000–1100 to (10,0), 1100–1200 to (10,10)).

```php
public function testRelatedObjectIsExportedInFull(): void
{
	$out = (new Neuron_GameServer_Map_MovingObjectsExporter('s', 30))->export([$this->obj('b:1', true, 'attack', 'A → B', "openWindow('battle',{'vid':1});")], 'viewer', 1050);
	$this->assertSame(1050, $out['servertime']);
	$this->assertSame(30, $out['poll']);
	$o = $out['objects'][0];
	$this->assertSame('b:1', $o['id']);
	$this->assertSame('attack', $o['kind']);
	$this->assertSame('A → B', $o['name']);
	$this->assertSame("openWindow('battle',{'vid':1});", $o['click']);
	$this->assertCount(2, $o['paths']);
	$this->assertSame(1000, $o['paths'][0]['start']);
	$this->assertSame('army.png', $o['sprite']['url']);
}

public function testUnrelatedObjectIsRedactedToOnePollWindow(): void
{
	$o = (new Neuron_GameServer_Map_MovingObjectsExporter('s', 30))->export([$this->obj('b:1', false, 'attack', 'A → B', 'x')], 'viewer', 1050)['objects'][0];
	$this->assertSame('neutral', $o['kind']);
	$this->assertStringStartsWith('a:', $o['id']);
	$this->assertSame(14, strlen($o['id']));
	$this->assertArrayNotHasKey('name', $o);
	$this->assertArrayNotHasKey('click', $o);
	$this->assertCount(1, $o['paths']);
	$this->assertSame(1050, $o['paths'][0]['start']);
	$this->assertSame(1080, $o['paths'][0]['end']);
	$this->assertEqualsWithDelta(5.0, $o['paths'][0]['points'][0][0], 0.001);
	$this->assertEqualsWithDelta(8.0, $o['paths'][0]['points'][1][0], 0.001);
}

public function testUnrelatedTruncationStopsAtMovementEnd(): void
{
	$p = (new Neuron_GameServer_Map_MovingObjectsExporter('s', 30))->export([$this->obj('b:1', false, 'attack', null, null)], null, 1090)['objects'][0]['paths'][0];
	$this->assertSame(1100, $p['end']);
}

public function testUnrelatedIdleObjectHasNoPaths(): void
{
	$o = (new Neuron_GameServer_Map_MovingObjectsExporter('s', 30))->export([$this->obj('b:1', false, 'attack', null, null)], null, 5000)['objects'][0];
	$this->assertSame([], $o['paths']);
	$this->assertEqualsWithDelta(10.0, $o['x'], 0.001);
}

public function testAnonymousIdIsStableAndDependsOnSecret(): void
{
	$a = new Neuron_GameServer_Map_MovingObjectsExporter('s1');
	$b = new Neuron_GameServer_Map_MovingObjectsExporter('s2');
	$this->assertSame($a->anonymousId('b:1'), $a->anonymousId('b:1'));
	$this->assertNotSame($a->anonymousId('b:1'), $b->anonymousId('b:1'));
}

public function testExporterSkipsObjectsThatThrow(): void
{
	$bad = new class extends Neuron_GameServer_Map_MapObject {
		public function getDisplayObject () { throw new RuntimeException ('no town centre'); }
		public function getName () { return 'bad'; }
	};
	$out = (new Neuron_GameServer_Map_MovingObjectsExporter('s'))->export([$bad, $this->obj('b:2', true, 'squad', 'S', null)], null, 1050);
	$this->assertCount(1, $out['objects']);
	$this->assertSame('b:2', $out['objects'][0]['id']);
}
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `docker compose exec -w /engine web sh -c 'composer dump-autoload && vendor/bin/phpunit tests/Map/MovingObjectsExporterTest.php'`
Expected: FAIL, class not found.

- [ ] **Step 3: Implement `MovingObjectsExporter.php`**

Per object inside `try { } catch (Exception $e) { error_log (...); continue; }`. Active movement = first `getMovements()` entry with `isActive($now)`.

- [ ] **Step 4: Run tests to verify they pass**

Run: `docker compose exec -w /engine web vendor/bin/phpunit tests/Map/`
Expected: PASS, and `vendor/bin/phpcs` clean.

- [ ] **Step 5: Commit**

```bash
git add src/Neuron/GameServer/Map/MovingObjectsExporter.php tests/Map/MovingObjectsExporterTest.php
git commit -m "Add MovingObjectsExporter with per-viewer redaction"
```

### Task 5: Rewrite the `map/objects` endpoint

**Files:**
- Modify: `src/Neuron/GameServer/scripts/map/objects.php` (replace the body below the license header)
- Modify: `src/Neuron/GameServer/scripts/map/map.php:29-39` (JSON content type now also applies to `objects`; it already does by default)
- Test: `tests/GameServerMapObjectsRouteTest.php`

**Interfaces:**
- Consumes: Task 3 `Regions::toAreas()`, `Regions::cacheKey()`; Task 4 exporter; `Neuron_GameServer::getPlayer()`; `$this->getMap ()->getMapObjectManager ()->getMovingObjects ($areas, $player)`.
- Produces: `Neuron_GameServer::renderMovingObjects(array $regions, int $tiles, $player, int $now): array` — a public method on `Neuron_GameServer` the script calls after reading input; returns `array('status' => 200|403, 'body' => array)`. The secret is `sha1 (DB_PASSWORD . 'army')`; `$tiles` from `Neuron_Core_Tools::clampInt ($_GET['tiles'] ?? null, 1, 10, 10)`.

- [ ] **Step 1: Write the failing test**

```php
class GameServerMapObjectsRouteTest extends TestCase
{
	public function testAnonymousIsForbiddenWithEmptyList(): void
	{
		$r = Neuron_GameServer::bootstrap()->renderMovingObjects([[0, 0]], 10, null, 1000);
		$this->assertSame(403, $r['status']);
		$this->assertSame(['objects' => []], $r['body']);
	}

	public function testLoggedInGetsExportFromManager(): void
	{
		$server = Neuron_GameServer::bootstrap();
		$server->setGame(new MapObjectsStubGame());   // getMap() returns a map whose manager yields one related object
		$r = $server->renderMovingObjects([[0, 0]], 10, 'player', 1050);
		$this->assertSame(200, $r['status']);
		$this->assertSame(1050, $r['body']['servertime']);
		$this->assertSame(30, $r['body']['poll']);
		$this->assertSame('b:1', $r['body']['objects'][0]['id']);
	}
}
```

`MapObjectsStubGame` implements `Neuron_GameServer_Interfaces_Game` with every method returning `null`/`array()` except `getMap()`, which returns a `Neuron_GameServer_Map_Map` subclass whose `getMapObjectManager()` returns an anonymous `Neuron_GameServer_Map_Managers_MapObjectManager` with `getDisplayObjects()` returning `array()` and `getMovingObjects()` returning one `ExportableObject` from Task 4 (move that double to `tests/Map/ExportableObject.php` so both tests share it). Read `Neuron_GameServer_Interfaces_Game` and `Neuron_GameServer_Map_Map` first for the exact method list.

- [ ] **Step 2: Run test to verify it fails**

Run: `docker compose exec -w /engine web vendor/bin/phpunit tests/GameServerMapObjectsRouteTest.php`
Expected: FAIL, undefined method `renderMovingObjects`.

- [ ] **Step 3: Implement `renderMovingObjects()` in `GameServer.php` and make `objects.php` a thin script**

`objects.php`: `$regions = Neuron_Core_Tools::sanitizeMapRegions ($_POST['regions'] ?? null, 100);` then `$r = $this->renderMovingObjects ($regions, $tiles, Neuron_GameServer::getPlayer (), NOW); http_response_code ($r['status']); echo json_encode ($r['body']);`. Delete the XML output and the hard-coded bubble. Caching is the game's concern (Task 9), not the engine's.

- [ ] **Step 4: Run tests to verify they pass**

Run: `docker compose exec -w /engine web sh -c 'vendor/bin/phpunit && vendor/bin/phpcs'`
Expected: PASS, clean.

- [ ] **Step 5: Smoke check with the game**

Run: `curl -s -o /dev/null -w '%{http_code}\n' -X POST --data 'regions[0][0]=0&regions[0][1]=0' 'http://localhost:8080/map/objects/'`
Expected: `403`.

- [ ] **Step 6: Commit**

```bash
git add src/Neuron/GameServer.php src/Neuron/GameServer/scripts/map/objects.php tests/
git commit -m "map/objects: region-aware JSON export of moving objects, logged-in only"
```

### Task 6: `armies.js` client layer

**Files:**
- Create: `assets/javascript/armies.js`
- Modify: `templates/gameserver/index.phpt:106` (add `armies.js` include after `gamemap.js`)
- Modify: `assets/javascript/gamemap.js` — in `onLoadImagesDone` after `enableControls ()` call `Game.armies.init ()`; at the end of `drawMapRegionCommit` call `Game.armies.poll ()`.
- Modify: `assets/css/` (the file that styles `#map`, find with `grep -rn "#map" assets/css`) — add `.armies` and `.army` rules.

**Interfaces:**
- Consumes: Task 5 endpoint at `CONFIG_DISPATCH_URL + 'map/objects/?tiles=' + Game.map.iTilesPerRegion`, POST body `regions[i][0]=x&regions[i][1]=y` for every key of `Game.map.oScrollMap.loadedMapTiles` (keys are `"i|j"`, values `[x, y]`); `Game.map.getOrthogonalCors`, `Game.map.oScrollMap.div`, `Game.map.onObjectClick`, `Game.core.sSessionId`.
- Produces: `Game.armies = { init, poll, tick, objects, offset, layer, canvas }` with `init ()`, `poll ()`, `tick ()`, `setObjects (json)`, `positionAt (obj, t)` returning `[x, y]` in tile units, `pixelFor (x, y)`.

- [ ] **Step 1: Write `armies.js`**

Behaviour fixed by the spec: layer div `class="armies"`, `position:absolute; left:0; top:0; z-index:20; pointer-events:none`; one `<canvas class="armies-routes">` child for dashed route lines of related objects (`setLineDash([6, 4])`), sized to the scroll div and redrawn in `setObjects`; one `div.army` per object with `pointer-events:auto`, `background-image: url(sprite.url)`, `title = name || ''`, data attribute `data-kind`, class `facing-left`/`facing-right`; `setInterval (Game.armies.tick, 1000)` started in `init`; `setInterval (Game.armies.poll, json.poll * 1000)` restarted when `poll` changes; `offset = json.servertime - (Date.now () / 1000)`; `positionAt`: find the path with `start <= t < end`, fraction `(t - start) / (end - start)`, linear between `points[0]` and `points[points.length - 1]`; before the first path use its first point, after the last use its last point, no paths use `[obj.x, obj.y]`; `pixelFor (x, y)` = `Game.map.getOrthogonalCors (x * -1, y)` minus half the sprite size (20 px); facing from the sign of the pixel x delta since the previous tick. Click on a `div.army` with `obj.click`: build a label `div.army-label` with `name`, kind text from `Game.armies.kindText[kind]` (filled by the game template, default the kind string) and ETA `Math.max (0, lastPath.end - now)` formatted `h:mm`, plus a button that runs `Game.map.onObjectClick (div, function () { eval (obj.click); })`; a second click removes the label. A failed request keeps the current objects.

- [ ] **Step 2: Wire it in `index.phpt` and `gamemap.js`, add CSS**

- [ ] **Step 3: Verify in the browser against the game stack**

With a related object faked from Task 5's stub (temporarily point `Dolumar_NewMap_MapObjectManager::getMovingObjects()` at a hard-coded object, or wait for Task 9), open `http://localhost:8080/`, check in dev tools: one `div.armies` inside the scroll div, a POST to `map/objects/` after load and every 30 s, the sprite moving 1 px-step per second, panning keeps it aligned with the tiles, tap on a phone-sized viewport (dev tools device mode) opens the label.

- [ ] **Step 4: Run the engine suite and phpcs (template is PHP)**

Run: `docker compose exec -w /engine web sh -c 'vendor/bin/phpunit && vendor/bin/phpcs'`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add assets/javascript/armies.js assets/javascript/gamemap.js assets/css templates/gameserver/index.phpt
git commit -m "Add armies.js: animated moving-objects layer on the map"
```

### Task 7: Engine docs and PR

**Files:**
- Modify: `.ai/architecture.md` (Map/ row: mention `Movement`, `MovingObjectsExporter`, `Regions`, and that `map/objects` is the JSON moving-objects endpoint), `.ai/development.md` if a new test directory needs mention.

- [ ] **Step 1: Update the docs, run the full suite once more, commit**
- [ ] **Step 2: Push `claude/project-thread-ped4we` and open the engine PR** (body starts with the attribution block from the session context, ends with the generated-with lines). Note in the body that the game PR follows and that nothing changes for the game until it implements `getMovingObjects()`.

## Phase 2: Game data and model (`dolumar`)

Commands run from the game checkout: `docker compose exec web vendor/bin/phpcs`, `tools/smoke.sh http://localhost:8080`, and the new `docker compose exec web vendor/bin/phpunit` after Task 8.

### Task 8: Route storage and `getRouteBetweenVillages()`

**Files:**
- Modify: `dump/dump.sql` (`battle`: add `` `route` text DEFAULT NULL `` after `iBattleSlots`; `squad_commands`: add `` `sc_route` text DEFAULT NULL `` after `s_to`)
- Modify: `tools/setup.php` (new idempotent step after the schema install: for each of the two columns, `SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?`, and `ALTER TABLE ... ADD COLUMN` when 0)
- Modify: `src/Dolumar/Map/Distance.php` (`getDistance()` → add `getDistanceAndRoute()`)
- Modify: `src/Dolumar/Map/Map.php:705` (add `getRouteBetweenVillages()`)
- Modify: `src/Dolumar/Players/Village/Battle.php:349-380` (`attackVillage`: store `route`)
- Modify: `src/Dolumar/Players/Squad.php:559-645` (`sendToVillage`: store `sc_route`)
- Create: `phpunit.xml.dist`, `tests/bootstrap.php` (Composer autoload only, no DB), `tests/Map/DistanceRouteTest.php`
- Modify: `composer.json` (`require-dev` phpunit `^11`, `scripts.test`), `.ai/development.md` (new test command), `AGENTS.md` quick commands (the "this repo has none of its own" remark goes).

**Interfaces:**
- Produces: `Dolumar_Map_Distance::getDistanceAndRoute($loc1, $loc2, $portals, $ignoreImpassable = true): array|false` — `array('distance' => float, 'route' => array of [x, y])`, route from start to destination inclusive, portal pairs as consecutive entries. `getDistance()` keeps its signature and returns `['distance']`.
- Produces: `Dolumar_Map_Map::getRouteBetweenVillages($village1, $village2, $ignoreImpassable = true): array|false` — same array, from the town centres; `getDistanceBetweenVillages()` unchanged.
- Produces: columns `battle.route`, `squad_commands.sc_route`: JSON `[[x,y],...]` or NULL.

- [ ] **Step 1: Write the failing test** (`getShortestDistance` is private and DB-free when `$ignoreImpassable = true` and there are no portals; test through the public method)

```php
class DistanceRouteTest extends TestCase
{
	public function testStraightRouteHasTwoPointsAndEuclideanDistance(): void
	{
		$r = Dolumar_Map_Distance::getDistanceAndRoute([0, 0], [3, 4], [], true);
		$this->assertEqualsWithDelta(5.0, $r['distance'], 0.001);
		$this->assertSame([[0, 0], [3, 4]], $r['route']);
	}

	public function testSamePointIsZeroWithOnePoint(): void
	{
		$this->assertSame(['distance' => 0, 'route' => [[1, 1]]], Dolumar_Map_Distance::getDistanceAndRoute([1, 1], [1, 1], [], true));
	}

	public function testGetDistanceStillReturnsNumber(): void
	{
		$this->assertEqualsWithDelta(5.0, Dolumar_Map_Distance::getDistance([0, 0], [3, 4], [], true), 0.001);
	}
}
```

The bootstrap must define the constants `Dolumar_Map_Distance` touches (check with `grep -n "define\|NOW\|GAME_" src/Dolumar/Map/Distance.php`; `Neuron_Profiler_Profiler` is used and must work without a DB).

- [ ] **Step 2: Run test to verify it fails**

Run: `docker compose exec web sh -c 'composer install && vendor/bin/phpunit'`
Expected: FAIL, undefined method.

- [ ] **Step 3: Implement `getDistanceAndRoute()`**: it is `getDistance()` with the route reversed into start-to-end order and the `'d'` keys stripped (`getRoute()` builds it destination-first). Then `getRouteBetweenVillages()`, the two dispatch call sites (`json_encode ($r['route'])` into the insert; distance used exactly as before), schema, setup step.

- [ ] **Step 4: Run tests, phpcs, setup and smoke**

Run: `docker compose exec web vendor/bin/phpunit` PASS; `docker compose exec web vendor/bin/phpcs` clean; `docker compose exec web php tools/setup.php` twice (second run must not error); `SHOW COLUMNS FROM battle LIKE 'route'` returns a row; `tools/smoke.sh http://localhost:8080` PASS.

- [ ] **Step 5: Commit**

```bash
git add dump/dump.sql tools/setup.php src/Dolumar/Map src/Dolumar/Players composer.json composer.lock phpunit.xml.dist tests .ai/development.md AGENTS.md
git commit -m "Store the A* route of attacks and squad moves; add a game PHPUnit suite"
```

### Task 9: `Dolumar_NewMap_Army` and its factory

**Files:**
- Create: `src/Dolumar/NewMap/Army.php`, `src/Dolumar/NewMap/ArmyFactory.php`
- Create: `public/static/images/sprites/armies/{attack,returning,squad,waiting,neutral}.png` (40 × 40 px, two 20 px facing frames side by side; placeholder art is fine, Thijs can replace)
- Test: `tests/NewMap/ArmyFactoryTest.php`

**Interfaces:**
- Consumes: engine Task 1–2 (`Movement`, `MapObject::addMovement/isRelatedTo/getKind`), `Neuron_GameServer_Map_Display_Sprite`.
- Produces: `Dolumar_NewMap_Army extends Neuron_GameServer_Map_MapObject` with constructor `(string $uoid, string $kind, array $relatedVillageIds, ?string $name, ?string $click, string $spriteUrl)`; `getUOID()`, `getKind()`, `getName()`, `getDisplayObject()`; `isRelatedTo($viewer)` returns `true` when `$viewer` is an array of village ids intersecting `$relatedVillageIds` (the manager passes the viewer's related set, see Task 10), `false` for `null`/empty.
- Produces: `Dolumar_NewMap_ArmyFactory::fromBattleRow(array $row, int $now, int $poll = 30): ?Dolumar_NewMap_Army` with row keys `battleId, vid, targetId, startDate, arriveDate, fightDate, endFightDate, endDate, isFought, route, fromX, fromY, toX, toY, fromName, toName`; returns `null` when a coordinate is `null`.
- Produces: `Dolumar_NewMap_ArmyFactory::fromSquadRow(array $row, int $now): ?Dolumar_NewMap_Army` with keys `sc_id, s_id, s_from, s_to, s_start, s_end` (unix ints), `sc_route, fromX, fromY, toX, toY, squadName, toName, homeVid`.
- Produces: `Dolumar_NewMap_ArmyFactory::segments(array $route): array` — list of `[[x0,y0],[x1,y1]]` walking segments; a route of 2 points is one segment; a route of 4 points is two segments (0→1 and 2→3); lengths are used to split a phase's time pro rata.
- Sprite URL: `STATIC_ABSOLUTE_URL . 'images/sprites/armies/' . $kind . '.png'` (check the constant name in the bootstrap).
- Kind on `getKind()` is the phase at `$now`: `attack`, `waiting`, `returning`, `squad`.

- [ ] **Step 1: Write the failing tests**

```php
private function row(array $o = []): array
{
	return $o + ['battleId' => 7, 'vid' => 1, 'targetId' => 2, 'startDate' => 1000, 'arriveDate' => 1100,
		'fightDate' => 1100, 'endFightDate' => null, 'endDate' => 1234, 'isFought' => 0, 'route' => null,
		'fromX' => 0, 'fromY' => 0, 'toX' => 10, 'toY' => 0, 'fromName' => 'A', 'toName' => 'B'];
}

public function testStraightMarchOut(): void
{
	$a = Dolumar_NewMap_ArmyFactory::fromBattleRow($this->row(), 1050);
	$this->assertSame('b:7', $a->getUOID());
	$this->assertSame('attack', $a->getKind());
	$this->assertSame('A → B', $a->getName());
	$this->assertEqualsWithDelta(5.0, $a->getLocation(1050)->x(), 0.001);
	$this->assertSame("openWindow ('battle', {'vid': 1});", implode(' ', $a->getEvents('click')));
}

public function testPortalRouteJumpsAtPortal(): void
{
	$a = Dolumar_NewMap_ArmyFactory::fromBattleRow($this->row(['route' => '[[0,0],[10,0],[100,0],[110,0]]', 'toX' => 110]), 1050);
	// two 10-tile segments share 100 s equally: at 1049 still on segment one, at 1051 on segment two
	$this->assertLessThan(11, $a->getLocation(1049)->x());
	$this->assertGreaterThan(99, $a->getLocation(1051)->x());
}

public function testOverdueUnprocessedFightWaitsAtTarget(): void
{
	$a = Dolumar_NewMap_ArmyFactory::fromBattleRow($this->row(), 1500, 30);
	$this->assertSame('waiting', $a->getKind());
	$this->assertEqualsWithDelta(10.0, $a->getLocation(1500)->x(), 0.001);
	$m = $a->getMovements();
	$this->assertSame(1530, end($m)->getEndTime());
}

public function testFoughtArmyReturnsHome(): void
{
	$a = Dolumar_NewMap_ArmyFactory::fromBattleRow($this->row(['isFought' => 1, 'endFightDate' => 1200, 'endDate' => 1333]), 1266);
	$this->assertSame('returning', $a->getKind());
	$this->assertEqualsWithDelta(5.0, $a->getLocation(1266)->x(), 0.05);
}

public function testWipedOutArmyHasNoReturn(): void
{
	$a = Dolumar_NewMap_ArmyFactory::fromBattleRow($this->row(['isFought' => 1, 'endFightDate' => 1200, 'endDate' => 1201]), 1150);
	$ends = array_map(fn ($m) => $m->getEndTime(), $a->getMovements());
	$this->assertSame(1200, max($ends));
}

public function testSquadMove(): void
{
	$a = Dolumar_NewMap_ArmyFactory::fromSquadRow(['sc_id' => 3, 's_id' => 9, 's_from' => 1, 's_to' => 2, 's_start' => 1000, 's_end' => 1100,
		'sc_route' => null, 'fromX' => 0, 'fromY' => 0, 'toX' => 0, 'toY' => 10, 'squadName' => 'First', 'toName' => 'B', 'homeVid' => 1], 1050);
	$this->assertSame('s:3', $a->getUOID());
	$this->assertSame('squad', $a->getKind());
	$this->assertEqualsWithDelta(5.0, $a->getLocation(1050)->y(), 0.001);
	$this->assertSame("openWindow ('squads', {'vid': 1});", implode(' ', $a->getEvents('click')));
}

public function testMissingTownCentreGivesNull(): void
{
	$this->assertNull(Dolumar_NewMap_ArmyFactory::fromBattleRow($this->row(['toX' => null]), 1050));
}

public function testRelatedness(): void
{
	$a = Dolumar_NewMap_ArmyFactory::fromBattleRow($this->row(), 1050);
	$this->assertTrue($a->isRelatedTo([2, 5]));
	$this->assertFalse($a->isRelatedTo([5]));
	$this->assertFalse($a->isRelatedTo(null));
}

public function testBadRouteJsonFallsBackToStraightLine(): void
{
	$a = Dolumar_NewMap_ArmyFactory::fromBattleRow($this->row(['route' => '{not json']), 1050);
	$this->assertEqualsWithDelta(5.0, $a->getLocation(1050)->x(), 0.001);
}
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `docker compose exec web sh -c 'composer dump-autoload && vendor/bin/phpunit tests/NewMap'`
Expected: FAIL, class not found.

- [ ] **Step 3: Implement `Army.php` and `ArmyFactory.php`**

Phase rules in `fromBattleRow()`: out = `startDate → arriveDate` over `segments(route)` split pro rata by segment length; waiting = `arriveDate → (isFought ? endFightDate : max (fightDate, now + poll))` zero-length at the target; return = only if `isFought` and `endDate > endFightDate + 1`, `endFightDate → endDate` over the reversed segments. Kind = phase whose movement is active at `$now` (`attack` before `arriveDate`, `waiting` until the fight end, `returning` after); a battle entirely in the past gives `returning` at its end point. Name `fromName . ' → ' . toName`, click `"openWindow ('battle', {'vid': {$vid}});"`, related ids `[vid, targetId]`. Squad: one phase `s_start → s_end`, name `squadName . ' → ' . toName`, click on `homeVid`, related ids `[s_from, s_to, homeVid]`.

- [ ] **Step 4: Run tests to verify they pass**

Run: `docker compose exec web sh -c 'vendor/bin/phpunit && vendor/bin/phpcs'`
Expected: PASS, clean.

- [ ] **Step 5: Commit**

```bash
git add src/Dolumar/NewMap/Army.php src/Dolumar/NewMap/ArmyFactory.php public/static/images/sprites/armies tests/NewMap
git commit -m "Add Dolumar_NewMap_Army and factory building movements from battle and squad rows"
```

### Task 10: `getMovingObjects()` in the game manager, related set, cache

**Files:**
- Modify: `src/Dolumar/NewMap/MapObjectManager.php` (add `getMovingObjects()`, `loadMovingRows()`, `relatedVillageIds()`)
- Create: `src/Dolumar/NewMap/MovingRows.php` (the two SQL queries and the bounding-box filter, so they can be tested without the manager)
- Test: `tests/NewMap/MovingRowsTest.php` (bounding-box filter only; the SQL is exercised by the smoke test)

**Interfaces:**
- Consumes: Task 9 factory; engine `Regions`/`Area` (`isWithin`, `overlaps`); `Neuron_Core_Memcache::getInstance()` (`hasCache/getCache/setCache`, no TTL: store `array('t' => NOW, 'rows' => ...)` and treat age > 20 s as a miss); `Neuron_GameServer_Player::getVillages()`, `getClans()`, `Dolumar_Players_Clan::getMembers()`.
- Produces: `Dolumar_NewMap_MovingRows::battles(Neuron_DB_Database $db, int $now): array` — the spec's query (town centre join `buildingType IN (1,3) AND destroyDate = 0`, village names as `fromName`/`toName`, `WHERE endDate > $now OR isFought = 0`); `::squads($db, $now)` — `squad_commands` joined to `villages_squads` (`homeVid = v_id`, `squadName = s_name`) and both town centres, `WHERE s_end > FROM_UNIXTIME($now)`, `s_start`/`s_end` selected with `UNIX_TIMESTAMP()`.
- Produces: `Dolumar_NewMap_MovingRows::touchesAreas(array $points, array $areas): bool` — true when the padded (1 tile) bounding box of `$points` overlaps any area (use `Neuron_GameServer_Map_Area::overlaps` with an `Area` built from the box centre and half-diagonal, or a plain min/max check).
- Produces: `Dolumar_NewMap_MapObjectManager::getMovingObjects(array $areas, $viewer = null): array` of `Dolumar_NewMap_Army`. The exporter later calls `isRelatedTo($viewer)` with the raw player, so `Army::isRelatedTo($viewer)` accepts either an array of village ids (tests) or a player object, for which it calls `Dolumar_NewMap_MapObjectManager::relatedVillageIds($viewer)` (memoised per request in a static keyed by player id).
- Produces: `Dolumar_NewMap_MapObjectManager::relatedVillageIds($player): array` — own village ids plus every village id of every member of every clan.

- [ ] **Step 1: Write the failing tests**

```php
public function testBoxOverlappingAreaTouches(): void
{
	$areas = Neuron_GameServer_Map_Regions::toAreas([[0, 0]], 10);   // centre (5,-5), radius 20
	$this->assertTrue(Dolumar_NewMap_MovingRows::touchesAreas([[0, 0], [100, 100]], $areas));
	$this->assertTrue(Dolumar_NewMap_MovingRows::touchesAreas([[26, 0], [30, 0]], $areas)); // padding makes 25 touch 25
	$this->assertFalse(Dolumar_NewMap_MovingRows::touchesAreas([[40, 40], [50, 50]], $areas));
}

public function testClanMemberOfDefenderIsRelated(): void   // uses stub player/clan objects with getVillages()/getClans()/getMembers()
public function testStrangerIsNotRelated(): void
public function testCacheMissWithoutMemcacheStillAnswers(): void   // Neuron_Core_Memcache double whose hasCache() returns false; getMovingObjects() still returns the factory output
```

- [ ] **Step 2: Run tests to verify they fail**, then **Step 3: implement**, **Step 4: tests + phpcs pass**.

- [ ] **Step 5: Database-backed checks in the stack**

Insert one battle for two seeded villages (`INSERT INTO battle ...` with `startDate = UNIX_TIMESTAMP()`, `arriveDate = +3600`, `fightDate = +3600`, `endDate = +7200`, `iBattleSlots = 1`) and log in. Expected: `POST /map/objects/` with the regions around those villages returns one object with `id` `b:<id>`, kind `attack`; as a second, unrelated test player it returns `a:...`, `neutral`, one path of 30 s. Then `docker compose stop memcached` and repeat: still 200 (`testCacheMissWithoutMemcacheStillAnswers` covers the code path with a stubbed cache returning false). Add `map/objects` (anonymous, expect 403) to `tools/smoke.sh`.

- [ ] **Step 6: Commit**

```bash
git add src/Dolumar/NewMap tests/NewMap tools/smoke.sh
git commit -m "Serve moving armies per region with per-viewer relatedness and a 20 s cache"
```

### Task 11: Language keys, docs, engine lock bump, game PR

**Files:**
- Create: `resources/language/{en,fr,de}/map.lng` with section `[armies]`: `attack = "Attacking army"`, `waiting = "Army at the gates"`, `returning = "Returning army"`, `squad = "Regiment on the move"`, `neutral = "An army"`, `open = "Open"` (French and German translations in the same shape).
- Modify: the template that prints the JS config block (`resources/templates/default/gameserver/index/header.phpt`, found by the mobile analysis) to emit `Game.armies.kindText = {...}` from those keys after `armies.js` is loaded (or in `Game.armies.init` read a global `CONFIG_ARMY_TEXT`).
- Modify: `.ai/architecture.md` (Map section: army layer, `map/objects`, route columns), `.ai/development.md` (phpunit command).
- Modify: `composer.lock` via `composer update catlabinteractive/dolumar-engine` once the engine PR is merged into the branch `composer.json` points to (`dev-php8`; see `.ai/repositories.md` for the branch rule — if the engine PR targets `master`, this step needs the owner's call on `composer.json`).

- [ ] **Step 1: Add keys and the config emission, verify the label shows translated kind text in the browser**
- [ ] **Step 2: Update docs, run `vendor/bin/phpunit`, `vendor/bin/phpcs`, `tools/smoke.sh http://localhost:8080`** — all PASS.
- [ ] **Step 3: Full manual walkthrough** (spec "Testing"): dispatch an attack and a squad move, watch leave / wait / return with the fight processed by opening the target village; log in as an unrelated player and confirm neutral sprites and short paths in the response; phone-sized viewport tap test.
- [ ] **Step 4: Commit, push, open the game PR** (attribution block first, generated-with lines last; link the engine PR; list the two schema columns and the deploy order: engine first, then game, `tools/setup.php` adds the columns on post-deploy).

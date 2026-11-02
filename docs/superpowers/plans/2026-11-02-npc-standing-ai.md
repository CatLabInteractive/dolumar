# NPC standing AI implementation plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Replace the passive goblin NPC with a tribe that hoards, holds a hidden per-player grudge, telegraphs
hostility in three steps, counter-attacks, makes peace, posts requests and reinforces friends.

**Architecture:** Pure decision classes under `src/Dolumar/Npc/` over plain arrays (snapshot in, actions out), one
executor that calls the existing game API, three small tables, and a thin `Dolumar_Players_Village_Ai::run()`
orchestrator driven by `cron/ai.php` every 5 minutes.

**Tech Stack:** PHP 8.4 (`Dolumar_*` classmap autoload, no namespaces), MySQL via `Neuron_Core_Database` /
`Neuron_DB_Database`, PHPUnit 11 for the new `tests/` suite, phpcs PHPCompatibility.

**Spec:** `docs/superpowers/specs/2026-11-02-npc-standing-ai-design.md`

## Global Constraints

- PHP `^8.4.27`; no new Composer dependencies; code style follows the existing tab-indented `Dolumar_*` classes.
- Never change gameplay for human-versus-human play. Only NPC-side behaviour and NPC-defender loot change.
- Schema changes are idempotent `CREATE TABLE IF NOT EXISTS`, applied by `Dolumar_Npc_Schema::ensure()` and mirrored
  in `dump/dump.sql`.
- Every number from the spec lives in `Dolumar_Npc_Config` as a constant; nothing is hard-coded elsewhere.
- Decision classes take arrays and return arrays or `Dolumar_Npc_Action` objects; they never touch the database or
  `NOW`.
- `NPC_AGGRESSION` env: `off`, `counter` (default), `full`.

## Review Focus

1. A player whose attacking village is gone by the time the war party is planned: fall back to their nearest active
   village, or skip without error (Task 9 test `testWarPartyFallsBackToNearestVillage`).
2. Two cron runs in a row over the same reports: standing must not drop twice (Task 6 test
   `testReportsBelowLastProcessedIdAreIgnored`).
3. An NPC with no squadless units and only busy squads: no attack, no exception (Task 10 executor guard, logged skip).
4. A transfer to the NPC that is both the envoy payment and a request delivery: count as payment first, never both
   (Task 7 test `testPaymentIsNotAlsoAGift`).
5. Standing beyond the range: clamp to [-100, 100] on every write (Task 5 test `testClamp`).

---

### Task 1: Test harness for the game repo

**Files:**
- Create: `phpunit.xml.dist`, `tests/bootstrap.php`
- Modify: `composer.json` (autoload-dev classmap `tests/`, script `test`), `.ai/development.md`

- [ ] Add `phpunit.xml.dist` mirroring the engine's (bootstrap `tests/bootstrap.php`, suite `tests`, fail on
      warning/deprecation/notice).
- [ ] `tests/bootstrap.php`: require `vendor/autoload.php`; `define('NOW', 1_700_000_000)` if undefined.
- [ ] Add a smoke test `tests/BootstrapTest.php` asserting `class_exists('Dolumar_Players_Village_Ai')`.
- [ ] Run `vendor/bin/phpunit` → 1 test passes.
- [ ] Commit `test: add PHPUnit harness for the game repo`.

### Task 2: Config and schema

**Files:**
- Create: `src/Dolumar/Npc/Config.php`, `src/Dolumar/Npc/Schema.php`
- Modify: `dump/dump.sql`, `.env.example`, `README.md`

**Produces:** `Dolumar_Npc_Config::aggression(): string`, constants `TIER_*`, `DECAY_*`, `ATTACKS_PER_DAY = 2`,
`PROTECTION_DAYS = 7`, `PROTECTION_RUNES = 10`, `MAX_ARMY_FRACTION = 0.6`, `TRUCE_SECONDS = 3*86400`,
`GIFT_WINDOW = 7*86400`, `ATTACK_WINDOW = 86400`, `RESERVE_PER_RUNE`, `REQUEST_COOLDOWN`, `SUPPORT_SECONDS = 86400`.
`Dolumar_Npc_Schema::ensure(): void` and `Dolumar_Npc_Schema::statements(): string[]`.

- [ ] Test `ConfigTest`: `aggression()` returns `counter` when env unset, `off`/`full` when set, `counter` on junk.
- [ ] Test `SchemaTest`: `statements()` has 3 statements, each starts with `CREATE TABLE IF NOT EXISTS`.
- [ ] Implement; append the same statements to `dump/dump.sql`; document `NPC_AGGRESSION` and
      `NPC_PROTECTION_RUNES` in `.env.example` and README.
- [ ] Run tests, commit `feat(npc): config and schema`.

### Task 3: Tribe names, hoard words, requests

**Files:**
- Create: `src/Dolumar/Npc/Tribe.php`, `tests/Npc/TribeTest.php`
- Modify: `src/Dolumar/Players/NPCPlayer.php` (village names unchanged; tribe names come from here)

**Produces:** `Dolumar_Npc_Tribe::generateName(callable $rand = null): string`,
`Dolumar_Npc_Tribe::multipliers(callable $rand = null): array{mul_threshold, mul_decay, mul_hoard}` in 0.8..1.2,
`Dolumar_Npc_Tribe::hoardWord(int $sum, int $capacity): 'lean'|'stocked'|'overflowing'` (< 25 %, < 75 %, else),
`Dolumar_Npc_Tribe::makeRequest(array $resources, float $mulHoard): array{res, amount}` (the lowest resource, amount =
clamp(25 % of hoard sum * mul, 300, 5000) rounded to 50).

- [ ] Tests for each (deterministic via injected `$rand`), then implement, run, commit `feat(npc): tribe identity`.

### Task 4: Snapshot value object and builder

**Files:**
- Create: `src/Dolumar/Npc/Snapshot.php`, `src/Dolumar/Npc/SnapshotBuilder.php`, `src/Dolumar/Npc/StandingRepository.php`

**Produces:** `Dolumar_Npc_Snapshot` with public array properties per the spec (`now`, `vid`, `resources`, `capacity`,
`runes`, `buildings`, `constructing`, `units`, `squads`, `tribe`, `standings`, `reports`, `transfers`, `neighbours`,
`incoming`, `thirdPartyAttacks`, `aggression`). `Dolumar_Npc_SnapshotBuilder::build(Dolumar_Players_Village $v, int
$now): Dolumar_Npc_Snapshot`. `Dolumar_Npc_StandingRepository::{tribe(vid), saveTribe(row), standings(vid),
save(row), log(vid, ?plid, message)}`.

- [ ] Implement (DB-backed, no unit tests; covered by the Docker check). Queries: reports `fromId/targetId/victory/
      execDate` with `reportId > last_report_id`; transfers with `t_id > last_transfer_id` and `t_isReceived = '1'`;
      neighbours via `BuildingMapper::getBuildingsFromTypeWithinRadius(x, y, 1, 100)` excluding NPCs; incoming
      `battle` rows with `fightDate > now` on neighbour villages; third-party reports on neighbour villages in 24 h.
- [ ] `php -l`, commit `feat(npc): snapshot and repository`.

### Task 5: Standing arithmetic

**Files:**
- Create: `src/Dolumar/Npc/Standing.php`, `tests/Npc/StandingTest.php`

**Produces:** static pure functions over a row array + multipliers:
`valueAt(row, now)`, `tier(row, now, mulThreshold)`, `applyAttack(row, now, victory)`, `applyGift(row, now, value,
hoard)`, `applyRequestDelivered(row, now)`, `makePeace(row, now, mulThreshold)`, `inTruce(row, now)`,
`lootFactor(row, now)`, `clamp(float)`, `newRow(vid, plid, now)`.

- [ ] Tests: `testNewRowIsNeutral`, `testDecayHostileSideOnePointPerHour`, `testDecayFriendlySideOnePointPerFourHours`,
      `testAttackPenaltyFormula` (victory 1, first attack → -40; second within 24 h → -60), `testWindowResets`,
      `testGiftOnlyBestInWindowCounts`, `testRequestDeliveredPlus30`, `testPeaceSetsWaryFloorAndTruce`,
      `testLootFactor` (0 → 1.0, -100 → 0.25, truce → 0, +50 → 1.0), `testTiersWithMultiplier`, `testClamp`.
- [ ] Implement, run, commit `feat(npc): standing arithmetic`.

### Task 6: Standing updater

**Files:**
- Create: `src/Dolumar/Npc/StandingUpdater.php`, `tests/Npc/StandingUpdaterTest.php`

**Produces:** `Dolumar_Npc_StandingUpdater::apply(Dolumar_Npc_Snapshot $s): array{rows: array, transitions: array,
last_report_id: int, last_transfer_id: int}`. Transition shapes: `['kind' => 'tier', 'plid', 'from', 'to']`,
`['kind' => 'peace', 'plid', 'by' => 'payment'|'defence']`, `['kind' => 'pact', 'plid']`, `['kind' => 'pact_broken', 'plid']`,
`['kind' => 'request_delivered', 'plid', 'vid']`.

- [ ] Tests: attack by player creates row and lowers standing; war party report where NPC was attacker and lost
      (`victory < 0.5`) → peace by defence; transfer ≥ envoy price while envoy open → peace by payment and not a
      gift (`testPaymentIsNotAlsoAGift`); transfer matching request → +30 and transition; other transfer → gift;
      `testReportsBelowLastProcessedIdAreIgnored`; allied row attacked → pact_broken.
- [ ] Implement, run, commit `feat(npc): standing updater`.

### Task 7: Economy planner

**Files:**
- Create: `src/Dolumar/Npc/Planner/Economy.php`, `tests/Npc/EconomyPlannerTest.php`

**Produces:** `Dolumar_Npc_Planner_Economy::plan(Dolumar_Npc_Snapshot $s): Dolumar_Npc_Action[]` returning at most
`scout` and one of `build`/`upgrade` with params `building` (class name) and optional `rune`.

- [ ] Tests: no build below reserve floor; skips buildings whose fixed rune is unavailable; picks a random-rune
      building's rune as the most plentiful available; no build with 2 under construction; defensive ratio when a
      row is wary (towers first); scout rules as today (cap 1.2 × strongest neighbour, < 20 available runes).
- [ ] Implement (port the ratio sort from the old `Ai.php`), run, commit `feat(npc): economy planner`.

### Task 8: Army planner

**Files:**
- Create: `src/Dolumar/Npc/Planner/Army.php`, `tests/Npc/ArmyPlannerTest.php`

**Produces:** `Dolumar_Npc_Planner_Army::plan(Dolumar_Npc_Snapshot $s): Dolumar_Npc_Action[]` of `train` actions with
`building` (id), `unit` (class), `amount`.

- [ ] Tests: picks, per idle finished training building, the trainable unit the army has least of; respects
      remaining capacity; wary rows prefer defensive stats (`defIn`/`defCav` sum) over melee.
- [ ] Implement, run, commit `feat(npc): army planner`.

### Task 9: War planner

**Files:**
- Create: `src/Dolumar/Npc/Planner/War.php`, `tests/Npc/WarPlannerTest.php`

**Produces:** `Dolumar_Npc_Planner_War::plan(Dolumar_Npc_Snapshot $s, array $rows): Dolumar_Npc_Action[]` with
actions `scout_raid{plid, target}`, `envoy{plid, price}`, `war_party{plid, target, fraction}`, `support{plid,
target, fraction}`, `recall_support{plid}`, `allied_strike{plid, target}`, `thank_you{plid, target}`,
`announce{plid, action, data}` and a `rows` side-channel via `withRows()`; aggression `off` yields only
announcements of requests.

- [ ] Tests: ladder order (wary → scouts once, then envoy once after scouts, hostile → war party); fraction of
      first war party = `min(0.6, last_penalty / 40 * 0.3)`, then ×1.5 per party up to 0.6; daily cap of 2;
      protection by age and runes; truce blocks; `testWarPartyFallsBackToNearestVillage`; friendly + incoming →
      support with `fraction = min(0.5, ...)`, recall after 24 h; allied + third-party attack → strike (only on
      `full`); request delivered → thank_you.
- [ ] Implement, run, commit `feat(npc): war planner`.

### Task 10: Executor and orchestrator

**Files:**
- Create: `src/Dolumar/Npc/Action.php`, `src/Dolumar/Npc/Executor.php`
- Modify: `src/Dolumar/Players/Village/Ai.php` (rewrite `run()`), `cron/ai.php`

- [ ] `Dolumar_Npc_Action` (`type`, `params`, `get(key, default)`).
- [ ] Executor maps each action to the game API (spec "Components"); squads: reuse idle home squads of the unit
      type, else `addSquad()` + `addUnits()`; attack slots from `getAttackSlots($target)`; skip and log when no
      units. Announcements: `Dolumar_Players_Logs::addLog($playerVillage, 'npc_'.$action, [tribeName, ...], true)`.
- [ ] `Ai::run()`: snapshot → updater → save rows and ids → planners → executor → `last_run`.
- [ ] `cron/ai.php`: CLI guard, `Neuron_Core_Lock` `npc_ai`, `Dolumar_Npc_Schema::ensure()`, spawn loop fixed.
- [ ] `php -l` all; commit `feat(npc): executor and cron orchestration`.

### Task 11: Touch points in existing code

**Files:**
- Modify: `src/Dolumar/Battle/Battle.php` (`takeAndDestroy`, `getHonourPenalty`), `src/Dolumar/Windows/PlayerProfile.php`,
  `resources/templates/default/villageProfile.tpl`, `resources/language/{en,nl}/logs.lng`,
  `resources/language/{en,nl}/village.lng`

- [ ] Loot factor hook for NPC defenders; honour 0 when either side is NPC.
- [ ] Profile rows for NPC villages: tribe (owner), hoard word, viewer's request.
- [ ] Log texts for the eight `npc_*` actions and profile keys.
- [ ] `php -l`, `phpcs`, commit `feat(npc): loot factor, profile and texts`.

### Task 12: Docs and PR

- [ ] `.ai/architecture.md`: NPC subsystem paragraph; `.ai/development.md`: `vendor/bin/phpunit` for the game repo.
- [ ] Run the full suite and phpcs; commit `docs: NPC AI`.
- [ ] Open the PR with the three phases and the manual Docker check listed as not run here.

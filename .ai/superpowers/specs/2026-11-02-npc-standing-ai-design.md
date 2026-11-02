# NPC standing AI: design

Date: 2026-11-02. Status: approved by the owner in the project thread "Make NPC players smarter"; built in one
pull request in three phases (see the plan in `../plans/2026-11-02-npc-standing-ai.md`).

## Goal

Make the computer-controlled goblin villages ("NPCs") worth interacting with. Today every NPC is the same passive
village: it scouts runes, builds toward a fixed ratio, trains the first unit it can, and never attacks or reacts
(`Dolumar_Players_Village_Ai::doWar` is a stub). Players can ignore or farm them with zero risk.

After this change an NPC village is a goblin tribe with a hoard, a hidden grudge and a memory:

- **Neutral** by default: it stockpiles, defends itself and never attacks. Its hoard is worth raiding.
- **Raided too often** it turns on the raider through a readable three-step ladder (scouts, an envoy naming a
  price, a war party), with proportional then escalating counter-attacks that the player can pay off or fight off.
- **Fed** through the tribe's own requests it becomes friendly: it reinforces the player when they are attacked and,
  at the top tier, raids whoever attacked the player.

The standing number is never shown. Players only feel it through what the tribe does, which is why every step
lands in their normal village log and battle reports.

Out of scope (later work): NPC clans with standings toward player clans, trade and tribute terms, personalities,
a player-initiated "call the horde" action.

## Player-facing behaviour

### Standing

Each NPC village keeps one standing per human player, a float from -100 to +100, default 0.

| Event | Effect |
| --- | --- |
| Player's attack on the NPC is fought | `-(15 + 25 * victory) * (1 + 0.5 * n)` where `victory` is the battle result (0..1) and `n` the number of earlier attacks by this player in the last 24 h |
| Player delivers the tribe's current request | +30, then a new request is posted after a cooldown |
| Player sends an unprompted gift | `+5 + 10 * min(1, value / hoard)` where value is the resource sum of the transfer and hoard the NPC's resource sum; only the largest gift in a rolling 7-day window counts |
| Player pays the envoy's price, or defeats a war party at home | grudge ends: standing set to the wary floor and a truce of 3 days starts |
| Time | drifts toward 0 at 1 point per hour while negative and 1 point per 4 hours while positive, computed continuously from the last change, not per cron tick |

Tiers, with base thresholds: hostile < -40, wary -40..-10, neutral -10..+30, friendly +30..+70, allied > +70.
Every NPC village gets fixed random multipliers at spawn (0.8..1.2) for its thresholds, its decay speed and its hoard
growth, so there is no lookup table that works for every tribe.

### What each tier does

| Tier | Behaviour |
| --- | --- |
| Neutral | Builds with a reserve floor so it carries a hoard; defends; never attacks. Its profile shows the hoard in words (lean, well stocked, overflowing) and its current request for the viewing player. |
| Wary | The war drums. Step 1, scouts: a raid of 1 to 2 goblins with no loot appears in the player's log, and the tribe shifts its build order toward towers and defensive units and keeps troops home. Step 2, the envoy: a log notification from the tribe naming a price in resources. Paying it (a market transfer to the tribe of at least that resource sum before the war party arrives) ends the grudge. Loot from raiding the tribe shrinks with standing. |
| Hostile | Step 3, the war party: a real counter-attack, visible in the log as an incoming attack. The first is sized from the force of the player's last attack (via the penalty it caused); each further attack while hostile is sized up toward the NPC's full available army, never from other players' strength. Target: the player's village that attacked, else their nearest village. At most 2 NPC attacks per player per 24 h; none against players younger than 7 days or below a rune floor. A defeated war party (the player wins at home) makes peace. Otherwise the grudge fades with time. Nothing restarts it except a new attack by the player. |
| Friendly | When one of the player's villages within range has an incoming attack, the tribe sends a support squad sized to what it can spare (never more than half its army). The squad is recalled after 24 h. |
| Allied | A log notification announces the pact in the tribe's voice. The tribe also attacks a village that attacked the player in the last 24 h, under the same daily cap and protection rules. The pact breaks on the first raid by the player against the tribe. |

Delivering a request also earns a one-time thank-you: a small support squad of goblins sent to the delivering
village for 24 h.

### Truce and loot

During a truce the tribe cannot turn hostile toward that player and has hidden its hoard: raids on it yield nothing.
Outside a truce, loot from an NPC village is multiplied by a factor that falls from 1.0 at standing 0 to 0.25 at
standing -100 (so farming pays less each time) and is 1.0 on the positive side.

### Tribe identity

Every NPC village gets a generated tribe name (first name, family name, holding) stored once. The village profile
shows the tribe as owner instead of "Gaia"; the NPC player record stays. Village names continue to be generated as
today.

### Safety rails

- `NPC_AGGRESSION` environment variable: `off` (today's behaviour plus the neutral improvements; no scouts,
  envoys, war parties or allied strikes), `counter` (the ladder and counter-attacks, no allied strikes), `full`.
  Default when unset: `counter`.
- Hard caps: 2 NPC attacks per player per 24 h, protection for accounts younger than 7 days or with fewer than
  `NPC_PROTECTION_RUNES` (default 10) total runes, the NPC never sends more than 60 % of its home army, and never
  attacks a village it cannot reach.
- The existing growth cap (1.2 times the strongest human within 100 tiles) stays. It bounds growth only and never
  sizes an attack.
- Every event is processed once: the NPC village remembers the highest battle report id and transfer id it has
  applied, so a cron rerun cannot double-count.

## Architecture

All new code lives under `src/Dolumar/Npc/` with the `Dolumar_Npc_` prefix. The decision logic is pure PHP over
plain arrays so it can be unit-tested without a database; only the snapshot builder and the executor touch the game.

```
cron/ai.php
  └─ Dolumar_Players_Village_Ai::run()          (thin orchestrator, keeps the $village->ai entry point)
       ├─ Dolumar_Npc_SnapshotBuilder            DB → Dolumar_Npc_Snapshot (arrays)
       ├─ Dolumar_Npc_StandingUpdater            pure: events + rows → rows + transitions
       ├─ Dolumar_Npc_Planner_Economy            pure: scout / build / upgrade decisions
       ├─ Dolumar_Npc_Planner_Army               pure: what to train
       ├─ Dolumar_Npc_Planner_War                pure: scouts, envoy, war party, support, allied strike, peace
       └─ Dolumar_Npc_Executor                   actions → game API calls, decision log
```

### Components

- `Dolumar_Npc_Config`: tuning constants (thresholds, decay rates, caps, protection, reserve floor, request sizes)
  and the `NPC_AGGRESSION` reader. Every number the spec names lives here.
- `Dolumar_Npc_Snapshot`: a value object holding, as arrays: `now`, the NPC village id, resources and capacity,
  runes (available per type, total, used), buildings (class → count, levels, under construction), home units
  (unit id → class, amount, squadless amount, space), existing idle squads at home, the tribe row (name, multipliers,
  last processed ids), standing rows keyed by player id, new battle reports against the NPC since the last run, new
  received transfers since the last run, nearby human villages (id, owner id, owner creation date, total runes,
  distance, reachable), incoming attacks on those villages, and recent attacks on those villages by third parties.
- `Dolumar_Npc_SnapshotBuilder`: builds the snapshot from the village object and direct queries on `battle_report`,
  `battle`, `villages_transfers(_items)`, `npc_villages` and `npc_standings`.
- `Dolumar_Npc_Standing`: pure functions over one standing row: `valueAt(now)`, `tier()`, `applyAttack()`,
  `applyGift()`, `applyRequestDelivered()`, `makePeace()`, `inTruce()`, `lootFactor()`, with the village
  multipliers applied.
- `Dolumar_Npc_StandingUpdater`: applies the snapshot's new reports and transfers to the rows, detects envoy
  payments, request deliveries and war parties defeated at home, and returns the updated rows plus a list of
  transitions (tier changes, peace, pact made, pact broken) for the executor to announce.
- `Dolumar_Npc_Planner_Economy`: returns at most one of scout, build or upgrade, honouring the reserve floor
  (`resources sum > RESERVE_PER_RUNE * used runes`), rune availability per building (fixed by `buildings.ini`), the
  two-under-construction limit, and a defensive ratio when any standing row is wary or worse.
- `Dolumar_Npc_Planner_Army`: picks the training building and unit so the army rotates through the trainable unit
  types rather than always the first, keeping within capacity.
- `Dolumar_Npc_Planner_War`: given rows, tiers, the aggression level and the nearby-village data, returns actions:
  `scout_raid`, `envoy`, `war_party` (target village, fraction of army), `support` (target village, fraction),
  `recall_support`, `allied_strike`, `thank_you`, and announcements. It enforces the daily cap, protection, truce
  and the 60 % home-army rule.
- `Dolumar_Npc_Action`: small value object (`type`, `params`).
- `Dolumar_Npc_Executor`: the only writer. Maps actions onto existing game calls: `buildings->build()`,
  `Building::upgrade()`, `scout()`, `Training::doTrainUnits()`, squads via `addSquad()/addUnits()`,
  `attackVillage()`, `Squad::sendToVillage()/goHome()`, `Dolumar_Players_Logs::addLog()` for the player-facing
  notifications, and `Dolumar_Npc_StandingRepository` for rows. Writes one line per decision to `npc_log` and to the
  cron output.
- `Dolumar_Npc_StandingRepository`: reads and writes `npc_villages` and `npc_standings`.
- `Dolumar_Npc_Tribe`: tribe name generation (moved from `Dolumar_Players_NPCPlayer::getNewVillageName`, which keeps
  generating village names), hoard words, request generation (a resource the tribe is short of, sized to its hoard).
- `Dolumar_Npc_Schema`: idempotent `CREATE TABLE IF NOT EXISTS` for the three tables, called once per cron run. The
  same statements go into `dump/dump.sql`.

### Data

```
npc_villages
  vid INT PK, tribe_name VARCHAR(60), mul_threshold FLOAT, mul_decay FLOAT, mul_hoard FLOAT,
  last_report_id INT, last_transfer_id INT, created INT, last_run INT

npc_standings
  vid INT, plid INT, PK (vid, plid)
  standing FLOAT, changed_at INT,
  attacks_window_start INT, attacks_in_window INT, last_attack_at INT, last_attack_vid INT, last_penalty FLOAT,
  scouts_at INT, envoy_at INT, envoy_price INT, war_parties_sent INT,
  npc_attacks_day INT, npc_attacks_today INT, last_npc_attack_at INT, last_npc_battle_id INT,
  truce_until INT,
  request_res VARCHAR(10), request_amount INT, request_created INT, request_delivered_at INT,
  gift_window_start INT, gift_best INT,
  support_squad INT, support_until INT,
  pact TINYINT

npc_log
  id INT AUTO_INCREMENT PK, vid INT, plid INT NULL, date INT, message VARCHAR(255)
```

Events are derived, not stored: `battle_report` (fromId, targetId, victory, execDate) for attacks on and by the
NPC, `villages_transfers` + items for gifts, requests and envoy payments, `battle` for incoming attacks on friendly
players. Standing rows are created lazily on the first event involving a player.

### Touch points in existing code

- `Dolumar_Players_Village_Ai`: rewritten as the orchestrator; same public `run($logger)`.
- `cron/ai.php`: CLI guard, a lock so runs never overlap, schema check, fixed spawn loop (today it only checks each
  player's last village because `lookForNPCs()` sits outside the inner loop).
- `Dolumar_Battle_Battle::takeAndDestroy()`: when the defender is an NPC, multiply loot by
  `Dolumar_Npc_Standing::lootFactor()` for the attacker's owner. `getHonourPenalty()` returns 0 when either side is an
  NPC (it also removes a PHP warning: the NPC player has no `lastRefresh`).
- `Dolumar_Windows_PlayerProfile::getVillageProfile()` and `villageProfile.tpl`: for NPC villages show tribe, hoard
  words and the viewer's current request.
- `resources/language/en/logs.lng` (and `nl`): new log actions `npc_scouts`, `npc_envoy`, `npc_warparty`,
  `npc_peace`, `npc_request`, `npc_thanks`, `npc_pact`, `npc_pact_broken`; `village.lng` profile keys for tribe,
  hoard and request.
- `.env.example`, `README.md`, `.ai/architecture.md`, `.ai/development.md`: configuration and the test command.

### Errors and edge cases

- An NPC village without a town centre is still deleted on the next run (existing behaviour).
- A player who deletes or loses the village that attacked the NPC: the war party targets their nearest village; a
  player with no active villages is skipped and their row decays normally.
- Unreachable targets (`getDistanceBetweenVillages` false) are skipped; the action is logged as skipped.
- If `attackVillage()` fails (no resources for special units never applies; squads busy), the executor logs the
  error and leaves the row unchanged so the next run retries.
- Clock: all timestamps are `NOW`-based integers; decay uses `changed_at`, so a missed cron run costs nothing.

## Testing

- Unit tests (`tests/`, PHPUnit, no database): `Dolumar_Npc_Standing` arithmetic, decay and tiers with
  multipliers; `StandingUpdater` against hand-built snapshots (attack, repeat attacks, gift windows, request
  delivery, envoy payment, peace by defence, pact made and broken, idempotency of processed ids);
  `Planner_Economy` (reserve floor, rune availability, construction limit, defensive shift); `Planner_Army`
  rotation; `Planner_War` (ladder order, daily cap, protection, truce, 60 % rule, aggression levels, target
  choice, support and recall, allied strike); `Tribe` name and hoard words. The game repo gets its first
  `phpunit.xml.dist` and `tests/bootstrap.php` (defines `NOW` and the few constants the classes read).
- Static checks: `php -l` on every changed file and `vendor/bin/phpcs` (PHP 8.4 compatibility).
- Manual check through the Docker stack, recorded in the PR: seed one NPC and one human village, run `cron/ai.php`,
  raid the NPC twice, watch the scouts and envoy appear in the log, pay or defend, and see peace; deliver a request
  and see the thank-you squad.

## Phases

1. Competent neutral NPC: `Dolumar_Npc_*` skeleton, snapshot, economy and army planners, reserve floor, tribe
   names, hoard words, decision log, schema, tests, cron fixes. No aggression yet.
2. Standing and hostility: standing rows, updater, war-drums ladder, counter-attacks, truce, loot factor, aggression
   switch, log texts.
3. Friendship: requests and thank-you squads, support and recall, allied pact and strikes.

# Battle report and Battle window redesign

Date: 2026-11-04. Requested by Thijs, who picked all three directions (A, B and C) from the options write-up in the
project folder (`battle-report/01-battle-report-options.md`, mockups in `battle-report/mockups/`).

## Goal

Make a battle report answer "did I win, what did I gain or lose, what is left of my army" on the first screen, tell
the fight as something worth retelling, and let players watch a fight play back (or play live). No gameplay, balance
or schema change: everything is derived from what `battle_report` already stores.

## Scope

- `Dolumar_Windows_Battle`: the Reports list and the report view. The attack and support flows are unchanged, apart
  from being reached through a button row on top of the list.
- Not changed: the Underworld battle window and the battle simulator. They keep using
  `Dolumar_Battle_Report::getReport()` and `battle/report.tpl`, which stay as they are.

## Structure

### Reports list (`battle/overview.phpt`)
- Button row: Attack (primary), Support, Formation.
- "Fighting now": reports of this village whose fight is still playing (`fightDate + fightDuration > now`), with the
  current phase and the time left, and a Watch link that opens the report in the replay view.
- Filter chips All / Attacks / Defences (input `filter`).
- One row per report from `battle_report` (`fromId` or `targetId` is this village, newest first, 10 per page),
  with a badge (attack or defence, won or lost), a title from the reader's side, the loot and the losses.

### Report (`battle/report/*.phpt`)
A "‹ All battles" link, the verdict header and a switch between three views. Input `view` = `summary`, `replay` or
`chronicle`; default is `summary` for a finished fight and `replay` for one still playing. The old `fightlog=1`
input maps to `replay` and `log` to the starting event.

- **Header (all views)**: verdict from the reader's side (Victory / Repelled for the attacker, Held off / Raided for
  the defender; a victory of 0.01 or more counts as a win for the attacker, as today), victory meter, opponent, date,
  fight length. Spoils (resources and runes, + for the attacker, − for the defender) and honour lost when it is above 0
  (`battle.iHonourLose`, attacker only).
- **A, Summary**: the two armies face to face (portrait, survivors bar, "103 of 120", HELD / FLED / WIPED), three or four
  key moments, the action buttons (Attack again or Strike back, Formation), and the full log in a folded `<details>`
  grouped by phase, with kill chips and plain-text rows.
- **B, Replay**: the battlefield with one token per unit (portrait, headcount, strength bar, status), play / pause /
  step, a phase scrubber, a caption with the current event, 1× / 2× speed. Live fights show a LIVE badge and follow
  the newest event; the 15 second poll re-renders the window and the player continues from where it was.
- **C, Chronicle**: the fight told per phase in short paragraphs built from aggregated events, margin notes with the
  numbers, the spoils, a closing verdict line and "Hero of the day" (the reader's unit with the most kills, or the
  winner's when the reader's side killed nothing).

### Key moments (pure rules, in order)
1. Magic: the first successful or failed spell.
2. The biggest volley (most kills in one ranged event).
3. The turning point: the first flee or wipe on the losing side (the side that ends with fewer survivors by share).
4. The reader's heaviest single loss.
Duplicates (same event) are dropped; at most four.

### Rounds
The log has phase markers (status 0..3) but no round markers. Within the melee phase a new round starts at the first
move, melee or stun event after a defender (team 2) melee or stun event, or after a flee or wipe. This matches the
order in `Dolumar_Battle_Fight::fight()` (morale check, moves, attacker melee, defender melee).

## Code

- `Dolumar_Battle_ReportAnalysis` (new, pure, unit tested): parses `battleLog`, `fightLog` and `slots` strings into
  events, phases with rounds, per-unit totals and fates, kills per unit, key moments, chronicle facts and replay frames.
  No database access; unit identity is the tuple (unit id, village id, race id, squad index).
- `Dolumar_Battle_ReportView` (new): resolves names and images, picks the reader's side, builds the template data and
  the replay JSON.
- `Dolumar_Battle_Report`: small public getters for the raw data.
- `public/dolumar/javascript/battlereport.js` and `public/dolumar/css/battlereport.css` (new), loaded from the game
  header; `initBattleWindow` starts the replay player.
- Texts: `battle.lng` section `report2`, `battlereport.lng` sections `moment_*`, `replay_*` and `chronicle_*`, in en, nl,
  de, fr and pl. The melee log line no longer shows the internal frontage number; `@@atacker` typo fixed.
- Mobile: the fight log is no longer a 115px scroll box.

## Not in this change

- "Share with my clan" from the chronicle mockup: it needs a way for clan members to open a report that is not theirs
  (a new access rule) and a chat link format in the engine. Left for a follow-up once the access rule is decided.
- Notifications still open the Battle window list.

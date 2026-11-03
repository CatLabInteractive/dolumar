# Browser notifications (Web Push)

Status: implemented in dolumar-engine (branch `claude/project-thread-0sjhem`) and this repository. Off until the
`VAPID_*` keys are set.

## Goal

Tell players about things in their kingdom while the game is closed or in a background tab: an incoming attack, a
fought battle, an army home, a finished building, something received. Standard Web Push, so it works in Chrome, Edge,
Firefox and Safari (on iOS only for a site added to the home screen) without Firebase or an Apple developer account.

## Design

- **Engine (generic).** `Neuron_GameServer_Player::sendNotification()` already rendered messages from the
  `notifications` language file for e-mail; every non-public message now also goes to `queuePushNotification()`.
  `sendPushNotification()` sends a push without e-mail. Messages are queued in `n_push_queue`; the cron delivers them
  with `minishlink/web-push` (`Neuron_GameServer_Push_Sender`), so no web request waits on a push service. Expired
  subscriptions (404/410) are deleted, messages older than an hour dropped. Routes `push/subscribe`,
  `push/unsubscribe` (logged in, same-origin JSON, endpoint host on an allowlist of push services) and `sw.js`.
  My Account gets a "Browser notifications" section: turn on/off per device and per-category toggles.
- **Game.** Categories and default-muted ones (`Dolumar_Players_Player`), strings (en, nl, fr, de; others fall back
  to English), `cron/push.php` with `Dolumar_Push_Events::scan()` for timed events, the manifest and icons.
- **Timed events are scanned, not scheduled.** Progress is lazy, so there is no "building finished" moment in code.
  The scanner looks at timestamps that passed since the last run (watermark `push_scan` in `n_server_data`, at most
  15 minutes of catch-up). A cancelled building or withdrawn attack never leaves a stale scheduled message. The one
  gap, a battle row deleted by `removeBattle()` before the scan sees its end date, is covered by a hook there.
- **Permission only on request.** The browser asks only when the player presses "Turn on" in My Account. The service
  worker shows nothing while a game tab is focused.

## Events

| Event | Section/key | Source |
|---|---|---|
| Village under attack | `battle/defending` | existing `sendNotification` at launch |
| Resources, runes, equipment received | `resources`, `runes`, `equipment` / `received` | existing calls |
| Clan portal opened | `portal/open(ed)` | existing calls, muted by default |
| Battle fought (attacker, defender) | `battle/fought_attacker`, `fought_defender` | scanner, `battle.fightDate` |
| Army home | `battle/returned` | scanner (`battle.endDate`) + `removeBattle()` hook |
| Building ready / upgraded | `building/ready`, `upgraded` | scanner, `map_buildings.readyDate` / `lastUpgradeDate` |

Public messages (written for other players' feeds: attack launched, clan join/leave, bonus building) are not pushed.

## Setup (owner)

1. `php tools/vapid-keys.php` once, then `dokku config:set dolumar VAPID_PUBLIC_KEY=… VAPID_PRIVATE_KEY=…`
   (optionally `VAPID_SUBJECT=mailto:…`). Never rotate the pair.
2. The tables come from `dump/upgrades/20261103-push-notifications.sql`, which `tools/setup.php` applies on every deploy.

## Not done

- A soft in-game prompt after a meaningful moment (now only My Account).
- Training and research finished.
- Translations for it and pl.
- The scanner reads `map_buildings` without an index on `readyDate`/`lastUpgradeDate`. Add one if the per-minute
  query shows up in the slow log.

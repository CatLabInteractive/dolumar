# Battle window: choosing a target

Date: 2026-11-05. Options write-up and mockups: project files `battle-targets/01-target-picking-options.md`.
Thijs picked A + C, with Nearby and the honour cost premium only (with a call to upgrade), the 20 closest villages,
networth instead of runes, and distances in leagues.

## Way back

- Every step after Attack / Support starts with "‹ Choose another target · All battles" (`battle/backLinks.phpt`),
  including the error pages (unreachable, own village, holiday, not found) and the "attack sent" message.
- The window keeps the picked `target` in its request data. `Dolumar_Windows_Battle::getContent()` now checks
  `overview` (back to the list) and `action` without `target` (choose again) before that, and resets the request data.

## Picker (`Dolumar_Battle_TargetList`, `battle/chooseTarget.phpt`)

Replaces `Neuron_Structure_ChooseTarget` in the Battle window only (Thieves and Market still use the engine one).

| Tab | Content | Free |
|---|---|---|
| Suggested | Attacks only, shown when there is something: strike back (attacked you in the last 7 days), avenge a clan mate (latest attack on a clan member by an outsider); premium adds the closest tribe and the closest player that costs no honour and you did not attack this week. Never suggests a village that costs honour or cannot be attacked. Max 4. | Yes (premium adds two kinds) |
| Nearby | The 20 closest active villages by straight line between town centres, filter All / Players / Tribes. Support: your villages and your clan first. | No: upgrade box |
| Recent | They attacked you (7 days), you attacked (with result), profiles you looked at (`villages_visits`, the old "latest targets") | Yes |
| Search | Name search, 10 results, exact match first | Yes |

Each row: name, owner (or "Tribe"), race, networth, distance in leagues (`Dolumar_Map_Map::tile2league`), and for
premium attacks the honour cost (`Dolumar_Battle_Battle::getHonourPenalty`). Clan members and players on holiday stay
in the list, greyed, with the reason. Free players land on Recent rather than on the locked tab; other tabs carry a
one-line "premium shows the honour cost" link.

Premium here means `isPremium()` (which includes the free first week). When credits are not configured on a server
(`Dolumar_Premium_Prices::creditsEnabled()` false), nobody could buy premium, so the picker is open to everyone.

The path distance and travel time stay on the next step: the path search is too heavy for 20 rows.

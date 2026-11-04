# Regiment editor (one screen to create and edit a regiment)

Date: 2026-11-04. Option A of the three designs in the project write-up (`army-regiments/01-regiment-ui-options.md`),
chosen by Thijs.

## What changed

- The Regiments window (`Dolumar_Windows_Squads`) has two pages instead of five: the list (`squads/squads.tpl`) and the
  editor (`squads/editor.tpl`). The old `squad`, `addUnits` and `removeUnits` pages all open the editor.
- List: one card per regiment (portrait, name, soldiers, weapon and armour chips, status line with Recall/Send back),
  a "Create new regiment" button and a line with the soldiers that are in no regiment.
- Editor, used for create and edit: unit tiles with free counts (create only), a prefilled name, one soldier count
  (target size, stepper plus None/Half/All), a weapon and an armour picker that show stock and grey out items there
  aren't enough of, one Save button, and Disband. The server computes the difference and calls the existing
  `Squad::addUnits()` / `removeUnits()` / `addEquipment()`.
- Suggested names: `Dolumar_Squads_NameSuggester`, "<ordinal> <unit name>" with the lowest number whose name is
  free in the village. The pattern is the `suggestedName` text (`@@ordinal`, `@@number`, `@@unit`); en/nl/fr use
  ordinals, de/pl use "Unit 2". Output always passes the `unitname` rule (transliterated, max 20 characters).
- `Squad::rename()` added.

## Look

Built from the style guide's `ui-*` kit (`.ai/style-guide.md`, `ui.css`): `a.ui-card` rows, one `ui-btn primary`,
`ui-field` labels, None/Half/All as links, `p.true`/`p.false` with `ui-msg`. `windows.css` keeps only layout for the unit
picker, the stepper and the header; `mobile.css` forces Verdana and blue links inside this sheet until the general
sheet rules are aligned.

## Rule change (approved by Thijs, 2026-11-04)

Adding soldiers to a regiment used to take off all its equipment. `Squad::addUnits()` now keeps each equipped item when
the village has one spare for every new soldier and only takes off the items it can't cover;
`Squad::getUnequippedItems()` lists those, and the editor says so. This also applies to the NPC executor.

## Not changed

No schema change. Regiments still hold one unit type. The name rule (`[a-zA-Z0-9 ]{3,20}`) is unchanged.

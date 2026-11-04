# Visual style guide

Agreed with Thijs on 2026-11-04. Every new or changed window, sheet and component follows this guide, on desktop and
on mobile. The components live in [`public/dolumar/css/ui.css`](../public/dolumar/css/ui.css); use them instead of
writing new button, card or tab styles.

The short version: **the classic 2009 Dolumar look, with a bit more room and a very light modern touch.** Plain
Verdana, blue links, parchment windows, the green/red/yellow/blue message boxes. No pills, gradients, coloured
banners or emoji.

Reference mockup (desktop window and mobile sheet built from this kit): `/mnt/project-files/style-guide/03-option-c-plus.png`
in the project's shared files, with the background in `01-style-guide-options.md` next to it.

## Rules

1. **One typeface: Verdana.** No Georgia or other serif headings, no system UI font, no UPPERCASE labels.
2. **Classic colours.** Text `#40331f`, links `#000097` underlined with red hover, headings black. Red means
   danger, error or hover; it never means "primary" or "press me".
3. **Decoration stays light.** Corners at most 3px. The only shadow is `0 1px 0 rgba(64,51,31,.2)` under a button.
   No gradients, no glows, no coloured banners. The parchment window frame is the decoration.
4. **The game's own icons.** Use `images/icons/`, the resource and rune spans (`span.resource.grain`) and unit
   images. Never emoji or Unicode pictographs (⚔ 🛡 ★ ☠ ✦).
5. **Links first, one main button.** Secondary actions are links. A window has at most one row of buttons for its
   main actions; the most important one is `.primary` (bold, dark border).
6. **Feedback uses the classic boxes**: `p.true`, `p.false`, `p.maybe`, `.information`, `.premium`. Add `.ui-msg`
   for the roomier padding. A result headline (Victory, Defeat) is a `p.true`/`p.false` with a
   `strong.ui-msg-title`. No new message or banner styles.
7. **Headings.** `h2` for a window section, `h3` (with its classic black underline) for sub-sections.
8. **Data is a table or a list.** Use `.ui-card` for rows the player opens (a regiment, a report), `.ui-box` for a
   bordered group, a table for numbers.
9. **Spacing.** 8px between elements on desktop, 10px on mobile; 16px above an `h3`.
10. **Mobile reuses the desktop look.** On mobile only sizes change: 44px tap height, 16px inputs, full width. The
    mobile chrome (header, tab bar, resource strip, sheet frame) is a separate, approved skin and stays as it is.
11. **Reuse before adding.** A new window adds no new button, card, tab, chip or banner class. If the kit lacks
    something, extend `ui.css` and this guide in the same pull request.

## Components (`ui.css`)

| Class | Use | Look |
|---|---|---|
| `a.ui-btn`, `button.ui-btn` | Main action, form submit | Flat `#f3e7c4`, 1px `#8a7550` border, 3px corners, 6px 14px padding; hover `#fbf3dc` |
| `.ui-btn.primary` | The most important action | Bold, `#40331f` border. Never red |
| `.ui-btn-row` (`.left`) | Buttons sharing the width (Attack / Support / Formation); `.left` keeps natural widths | 8px gap |
| `a.ui-card` | A row the player opens | 1px `#c9b58a` border, light card fill, 40px portrait `img`, `.ui-card-title` (bold link), `.ui-card-go` chevron `›` |
| `.ui-tabs` + `.ui-tab-panel` | View switch and its content | Tabs touch; inactive: light border and fill on a dark baseline; `a.active`: bold, dark border, no fill, no bottom line, open into the panel; panel: dark border on sides and bottom, 8px padding |
| `.ui-box`, `.ui-columns` | Bordered group, two-column grid of groups | Same border and fill as a card |
| `.ui-bar` with an `<i style="width:N%">` | Health or progress | 6px, green part `#2f7a1f`, rest `#b3261e` (`.neutral` for a tan rest) |
| `.ui-field` | Label above its input | Bold label, input with 1px `#8a7550` border and 3px corners |
| `.ui-muted`, `.ui-good`, `.ui-bad` | Secondary text, good and bad numbers | `#7a6a4c`, `#2f7a1f`, `#b3261e` |
| `p.true.ui-msg` etc. | Classic message box with room | 8px 10px padding, 3px corners |

Example:

```html
<div class="ui-btn-row">
	<a class="ui-btn primary" href="javascript:void(0);" onclick="...">Attack</a>
	<a class="ui-btn" href="javascript:void(0);" onclick="...">Support</a>
</div>

<p class="true ui-msg"><strong class="ui-msg-title">Victory</strong>Your attack on <a href="...">Tester5's Village</a>.</p>

<div class="ui-tabs">
	<a class="active" href="javascript:void(0);">Summary</a>
	<a href="javascript:void(0);" onclick="...">Replay</a>
</div>
<div class="ui-tab-panel">...</div>
```

Kit classes all start with `ui-` because older templates already use plain names such as `.tabs`.

## Not yet aligned (2026-11-04)

These parts predate the guide and still clash. Bring them in line when you touch them, or in a dedicated pull
request:

- `mobile.css`: the sheet button rule (`body.layout-mobile .sheet-content button`, `a.action-button`) draws cream
  and red gradient pills; sheet links are brown instead of blue; sheet text uses the system UI font and sheet titles and headings use Georgia
  (`ui.css` already restores the kit look for `ui-*` components inside sheets).
- `build.tpl`: "Construct building" and "Bonus buildings" use `a.action-button` (pill on mobile, link on desktop).
- Regiments (`.squads` in `windows.css`, PR #74): own gradient buttons, chips, Georgia names, uppercase labels.
- Battle report (`battlereport.css`, PR #81): verdict banners, chips, gradient switch, emoji icons, Georgia.

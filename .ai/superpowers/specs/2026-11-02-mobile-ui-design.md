# Dolumar: mobile-usable UI — design

Date: 2026-11-02
Status: draft, awaiting approval

## Intent and why

Dolumar's client is a desktop metaphor: a full-screen isometric map with draggable windows on top, built in 2008 on
Prototype 1.6, script.aculo.us and Prototype Window Class. On a phone the page forces the viewport to 50% zoom
with zooming disabled, so players get a shrunken desktop with tap targets a few pixels wide. Windows cannot be
dragged or closed reliably, menus need hover, and placing a building needs a mouse and a right-click.

The owner wants the **full game** to be playable on a phone: the map, building, units, economy, reports, chat and
account, not a "check-in" subset. The desktop UI must stay as it is for existing players.

Success = a player on a phone (Safari on iOS, Chrome on Android, portrait) can log in, read their resources, open
every window the desktop has, place a building on the map, zoom and pan the map, and switch between windows without
anything being cut off or too small to tap. A desktop player sees no change unless they opt in.

## Goals

1. A **responsive shell**: below a width threshold the same server-rendered windows are shown as full-screen sheets,
   the menu as a bottom tab bar and the status bar as a compact header.
2. A **layout toggle** (auto / desktop / mobile) saved per player, with a URL override for testing.
3. **Touch map interaction**: pan (already works), tap objects (already works), pinch-to-zoom with a +/- control, and a
   touch flow for placing buildings and selecting locations.
4. **Window content** readable and tappable on a narrow screen, most-used windows first.
5. Shipped behind the toggle so it can be tried by a few players before it becomes the default on small screens.

## Non-goals

- Changing the desktop layout, the window library, or the look of desktop windows.
- A new client, a new API, or replacing Prototype.js.
- Changing any PHP window's logic, the dialog XML protocol, polling or form handling.
- A native app or a PWA manifest (a later, independent step).
- Gameplay or balance changes.

## Anti-goals

- Desktop players noticing a behaviour change without having touched the toggle.
- Two implementations of the same window (one for desktop, one for mobile).
- A window that works on desktop but silently stops polling or submitting on mobile.

## Current state (facts)

- Shell page: engine `templates/gameserver/index.phpt`; game adds `resources/templates/default/gameserver/index/`
  `header.phpt` (CSS, `dolumar.js`, the `initial-scale=0.5, user-scalable=no` viewport), `body.phpt`, `jssettings.phpt`.
- Window manager: engine `assets/javascript/gui.js` (`Game.gui`). `openNewWindow()` requests `dialog` XML; the response
  carries attributes `type` (`window`, `panel`, `invisible`), `width`, `height`, `minWidth`, `minHeight`, `left/top/right/bottom`,
  `center`, `modal`, `closable`, `className`, `pollInterval`, `pool`, `onLoad`. For `window` it builds a Prototype
  Window Class dialog (`windowjs/window.js`, mouse events only) constrained to `top: 31`; for `panel` an absolutely
  positioned `div.panel`. Open windows are tracked in `Game.gui.aOpenWindows`; closing goes through `onDestroy` →
  `Game.gui.destroyWindow()`. Polling uses the `pool` and `pollInterval` of each open window.
- Initial windows (`Dolumar_Game::getInitialWindows()`): Welcome, ChatPopper, MiniMap, Menu, Newsbar, Statusbar, Guide,
  Chat, MyAccount. Menu, Newsbar and Statusbar are `panel` type with fixed positions and sizes
  (`src/Dolumar/Windows/{Menu,Newsbar,Statusbar}.php`, `resources/templates/default/menu.tpl`, `public/dolumar/css/menu.css`).
  Menu sub-menus (village lists) open on `onmouseover`.
- Map: engine `assets/javascript/scrollmap.js` (`TiledImageViewer`) with `touchstart/move/end` panning already in place,
  and `gamemap.js` which draws objects to canvas and adds image-map `<area>` elements with `click` handlers.
  Tile size is fixed server-side (`CONFIG_MAP_TILESIZE`); `setTilesizes()` exists but is unused.
- Location selection (`Game.gui.selectLocation` → `Game.map.selectLocation` → `TiledImageViewer.selectLocation`):
  hides all windows, draws a ghost image (`tivpointer`) that follows `mousemove`, confirms on `mousedown`, cancels on
  right-click (`oncontextmenu`), shows `#map_cancel_warning`.
- Player preferences: `Neuron_GameServer_Player::getPreference()/setPreference()` backed by `n_players_preferences`
  (key/value). `hide_chat` and `hide_advertisement` use the session instead.
- Window content: 46 game window classes with `setSize()` in pixels (250 to 600 wide); 37 templates use tables,
  19 fixed pixel widths, 12 hover tooltips. No media queries anywhere.
- Tests: engine has PHPUnit under `tests/`; the game has `tools/smoke.sh` (curl). No JavaScript tests.

## Design

### 1. Layout mode (engine)

New `assets/javascript/layout.js`, loaded before `gui.js`, defining `Game.layout`:

- `Game.layout.preference`: `'auto' | 'desktop' | 'mobile'`, injected by the shell page from the player preference
  `layout` (default `auto`); a `?layout=` query parameter overrides it for the session.
- `Game.layout.mode()`: `'mobile'` when preference is `mobile`, or preference is `auto` and
  `window.innerWidth < 800` (the breakpoint is a constant, 800 chosen so tablets in portrait get mobile and
  landscape get desktop); otherwise `'desktop'`.
- On load, resize and `orientationchange` it sets `body.layout-mobile` / `body.layout-desktop`. Switching mode while
  windows are open re-renders their containers (see 2) without re-requesting content.
- The mode is decided client-side, so the game header template always emits a normal viewport
  (`width=device-width, initial-scale=1`) and `layout.js` rewrites the meta to today's `initial-scale=0.5,
  user-scalable=no` only in desktop mode on a touch device. Desktop-mode phones therefore keep today's behaviour.

### 2. Windows as sheets (engine)

In `Game.gui.openNewWindow` response handling, when `Game.layout.mode() === 'mobile'` and `type === 'window'`:

- Instead of `new Window(settings)`, build a `div.sheet` inside `#sheets` (a fixed container between header and tab
  bar): title bar with a back button and a close button, the title, and a scrollable content area that receives the
  same `setHTMLFromXML(div, content)`. The entry in `aOpenWindows` keeps `oWindow = null` plus a new `oSheet` so the
  rest of `gui.js` (polling, `getWindowFromElement`, `destroyWindow`, `updateContent`) keeps working; every place that
  touches `oWindow` already guards for `null` because panels use that path.
- A sheet stack: opening a sheet pushes it and hides the previous one (`display: none`, still in the DOM, still
  polling). Back pops the top sheet (same as close for its window) and reveals the previous one. Close on the last
  sheet returns to the map. Windows flagged `allowOnlyOnce` that are re-opened move to the top of the stack.
- `modal` windows render as a centred card over the current sheet with a dimmed backdrop, not as a sheet.
- `width`, `height`, `left/top/right/bottom`, `center` and `minWidth/minHeight` are ignored in mobile mode.
- `panel` windows keep their `div.panel`; the game's mobile CSS repositions them (see 4).
- `hideAllWindows`/`showAllWindows` hide and show the `#sheets` container in mobile mode.
- Desktop mode is byte-for-byte the current code path.

### 3. Map on touch (engine)

- **Zoom**: `TiledImageViewer` gets `setScale(s)` with `s` clamped to `[0.5, 1.5]`, applying `transform: scale(s)`
  with `transform-origin: 0 0` on the scroll `div`. `resize()` divides the viewport size by the scale when computing
  `displaySizeX/Y`, and `drawVisibleImages()`/the drag maths convert screen deltas by `1/s`, so tiles keep loading and
  panning stays under the finger. Image-map areas are children of the scaled element and need no change.
  Pinch: on `touchstart` with two touches record the distance and current scale; on `touchmove` set the scale
  continuously; on `touchend` persist it. A `+`/`-` control (game header) calls `setScale(s ± 0.25)`. The level is stored
  in `localStorage` per device. Desktop mode stays at scale 1.
- **Selecting a location** (`selectLocation` in mobile mode): hide the sheets, show the ghost image centred in the
  viewport, fixed to the screen while the map pans under it, and show a bottom action bar with Cancel and Place. Place
  converts the viewport centre to map coordinates with the existing click-to-tile maths (`selectLocationClick(x, y)`)
  and calls the same callback; Cancel calls the same cancel path. `#map_cancel_warning` is not shown in mobile mode.
  The mouse-driven flow is unchanged in desktop mode.
- Tapping a map object keeps its `click` handler.

### 4. Chrome and content (game)

- **Header**: the Statusbar panel is restyled in mobile mode to a fixed top bar: resources in one scrolling row, plus
  buttons for zoom `+/-`, minimap, chat and news, which open those windows as sheets.
- **Tab bar**: the Menu panel is restyled to a fixed bottom bar with the same `openWindow` links; items beyond five go
  into a "More" sheet; the hover village sub-menus become a village picker opened by long-press or from the More sheet.
  The existing `menu.tpl` gets `data-` attributes and a few wrapper elements so one template serves both layouts;
  no second template.
- **Newsbar, MiniMap, Chat, ChatPopper, Guide, Welcome**: hidden by default in mobile mode; reachable from the header.
- **Mobile stylesheet** `public/dolumar/css/mobile.css`, scoped under `body.layout-mobile`: sheet layout, 44px tap
  targets for buttons, links in lists and form controls, fixed widths turned into `max-width: 100%`, wide tables
  wrapped in a horizontally scrolling container by a small `onUpdateContent` hook, tooltips toggled on tap.
  First pass covers Build, Economy, Squads/Units, Logbook, MyAccount, Village and Player profiles, Reports; the rest
  get the generic rules.
- **Toggle**: a `layout` select (auto / desktop / mobile) in the MyAccount window, saved with `setPreference('layout')`.
  The shell injects it as `CONFIG_LAYOUT` next to the other `CONFIG_*` values.

### 5. Testing

- Engine PHPUnit: preference round-trip for `layout`, and the shell exposing `CONFIG_LAYOUT` and the `?layout=`
  override (template-level tests like the existing route tests).
- Client: a Playwright script `tools/mobile-smoke.js` run against the Docker stack at a 390×844 viewport and at
  1280×800: load the shell, assert the body layout class, open each tab, open Build and place a building through the
  touch flow, pinch-zoom via a synthetic two-touch gesture, switch the toggle and assert desktop windows come back.
  Documented in `.ai/development.md`. Desktop run guards against regressions in the untouched path.
- `tools/smoke.sh` keeps passing.

### 6. Rollout

1. PR 1, `dolumar-engine`: `layout.js`, sheets in `gui.js`, map scale and touch placement in `scrollmap.js`/`gamemap.js`,
   preference plumbing, tests. No visible change without the game's CSS and toggle.
2. PR 2, `dolumar`: header/tab bar restyling, `mobile.css`, viewport change, toggle in MyAccount, `CONFIG_LAYOUT`,
   Playwright smoke, docs. Depends on PR 1 via `composer.lock`.
3. PR 3+, `dolumar`: per-window content polish as issues come in.

`auto` is the intended default and activates mobile mode below 800px. To stage the release, PR 2 ships with the
default preference set to `desktop`, so nothing changes until a player picks `mobile` or `auto` in MyAccount. A
follow-up one-line change switches the default to `auto` once a few players have tried it.

## Open questions

None blocking. Breakpoint (800px) and zoom range (0.5–1.5) are constants to tune after the first device test.

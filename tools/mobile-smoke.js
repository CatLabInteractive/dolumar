#!/usr/bin/env node
'use strict';
/**
 * Browser smoke checks for the mobile UI.
 *
 * Usage: node tools/mobile-smoke.js <baseUrl> [--only=check,check]
 * Needs Node 18+ and Playwright with Chromium (npm install --no-save playwright && npx playwright install chromium).
 * Exit codes: 0 all selected checks passed, 1 a check failed, 2 bad usage.
 */

let playwright;
try {
	playwright = require('playwright');
} catch (e) {
	console.error('The "playwright" package is missing. Run: npm install --no-save playwright && npx playwright install chromium');
	process.exit(2);
}

const checks = [];

function check(name, options, fn) {
	checks.push({ name, options, fn });
}

/**
 * Log in through the dev OIDC mock: loading "/" redirects to the provider and straight back. A brand-new
 * player lands on the "choose nickname" form, which is completed here so later checks can open game windows.
 * A player who has not started playing yet is then taken through the MyAccount race selection step (first race,
 * random position) so the account becomes a playing one.
 */
async function login(page, baseUrl) {
	const start = new URL('/', baseUrl);
	if (page.__layout) {
		start.searchParams.set('layout', page.__layout);
	}
	await page.goto(start.toString(), { waitUntil: 'load' });
	await page.waitForFunction(() => typeof CONFIG_GAME_NAME !== 'undefined', null, { timeout: 15000 });

	const nickname = page.locator('input[name=username]');
	if (await nickname.first().waitFor({ state: 'visible', timeout: 8000 }).then(() => true).catch(() => false)) {
		await nickname.first().fill('Smoke' + String(Date.now()).slice(-5));
		await nickname.first().locator('xpath=ancestor::form').locator('button[type=submit]').click();
		await page.waitForTimeout(1500);
	}

	// Complete race selection (MyAccount window) for a player who is not playing yet.
	const race = page.locator('form#selectrace');
	if (await race.first().waitFor({ state: 'visible', timeout: 5000 }).then(() => true).catch(() => false)) {
		await race.first().locator('button[type=submit], input[type=submit]').first().click();
		await page.waitForTimeout(3000);
		// Reload so every window reflects the now-playing account.
		await page.reload({ waitUntil: 'load' });
		await page.waitForFunction(() => typeof CONFIG_GAME_NAME !== 'undefined', null, { timeout: 15000 });
	}

	// The OIDC redirect drops the ?layout= query string; load the shell once more with it.
	if (page.__layout) {
		await page.goto(new URL('/?layout=' + page.__layout, baseUrl).toString(), { waitUntil: 'load' });
		await page.waitForFunction(() => typeof CONFIG_GAME_NAME !== 'undefined', null, { timeout: 15000 });
	}

	// Wait until the map has loaded and the "Loading..." dialog is closed.
	await page.waitForFunction(() => Game.core && Game.core.isMapLoaded && Game.gui.bIsInDialog <= 0, null, { timeout: 15000 });
}

function assert(condition, message) {
	if (!condition) {
		throw new Error(message);
	}
}

check('desktop-shell', { viewport: { width: 1280, height: 800 } }, async (page) => {
	assert(await page.locator('body.layout-desktop').count() === 1, 'body has no layout-desktop class');
	assert(await page.locator('.dialog').count() > 0, 'no .dialog element on the page');
	assert(await page.locator('.sheet').count() === 0, 'unexpected .sheet element on the desktop layout');
});

check('mobile-shell', { viewport: { width: 390, height: 844 }, layout: 'mobile' }, async (page) => {
	assert(await page.locator('body.layout-mobile').count() === 1, 'body has no layout-mobile class');
	assert(await page.locator('.dialog').count() === 0, 'unexpected .dialog element on the mobile layout');
});

// Save the layout preference of the mock player through the MyAccount form and reload without ?layout=.
async function saveLayoutPreference(page, baseUrl, value) {
	const select = page.locator('select[name=layout]').first();
	await page.evaluate(() => openWindow('MyAccount'));
	await select.waitFor({ state: 'visible', timeout: 15000 });
	await select.selectOption(value);
	await page.waitForSelector('body.layout-' + (value === 'auto' ? 'desktop' : value), { timeout: 2000 });
	await page.waitForTimeout(1000);
	await page.goto(baseUrl, { waitUntil: 'load' });
	await page.waitForFunction(() => typeof Game !== 'undefined' && Game.core && Game.core.isMapLoaded && Game.gui.bIsInDialog <= 0, null, { timeout: 15000 });
}

check('layout-toggle', { viewport: { width: 1280, height: 800 } }, async (page) => {
	const baseUrl = page.url().split('?')[0];

	// A run that stopped half-way may have left the shared mock player on mobile.
	if (await page.locator('body.layout-desktop').count() !== 1) {
		await saveLayoutPreference(page, baseUrl, 'auto');
	}
	assert(await page.locator('body.layout-desktop').count() === 1, 'body has no layout-desktop class to start with');

	try {
		await saveLayoutPreference(page, baseUrl, 'mobile');
		// The preference is saved on the server: a plain reload (no ?layout=) stays mobile.
		await page.waitForSelector('body.layout-mobile', { timeout: 15000 });
	} finally {
		// Always leave the mock player on the default (auto), also when the check failed.
		await page.setViewportSize({ width: 1280, height: 800 });
		await page.goto(baseUrl + '?layout=desktop', { waitUntil: 'load' });
		await page.waitForFunction(() => typeof Game !== 'undefined' && Game.core && Game.core.isMapLoaded && Game.gui.bIsInDialog <= 0, null, { timeout: 15000 });
		await saveLayoutPreference(page, baseUrl, 'auto');
	}
	await page.waitForSelector('body.layout-desktop', { timeout: 15000 });
});

// The default preference (auto) gives a phone the mobile layout without ?layout=.
check('phone-default', { viewport: { width: 390, height: 844 } }, async (page) => {
	assert(await page.evaluate(() => Game.layout.preference) === 'auto', 'default layout preference is not auto');
	assert(await page.locator('body.layout-mobile').count() === 1, 'a phone without a saved preference does not get the mobile layout');
});

// Desktop layout on a phone: the 0.5-scale viewport is set while the head is parsed (before
// DOMContentLoaded, not at window.load), and the meta is not rewritten when nothing changed.
check('desktop-touch-viewport', { viewport: { width: 390, height: 844 }, layout: 'desktop', recordViewport: true }, async (page) => {
	assert(await page.locator('body.layout-desktop').count() === 1, 'body has no layout-desktop class');
	const expected = await page.evaluate(() => Game.layout.DESKTOP_TOUCH_VIEWPORT);
	const state = await page.evaluate(() => ({
		atDomReady: window.__viewportAtDomReady,
		now: document.getElementById('viewport').getAttribute('content'),
		writes: window.__viewportWrites,
	}));
	assert(state.atDomReady === expected, 'viewport at DOMContentLoaded is "' + state.atDomReady + '", expected "' + expected + '"');
	assert(state.now === expected, 'viewport is "' + state.now + '", expected "' + expected + '"');
	assert(state.writes === 0, 'viewport meta was written ' + state.writes + ' time(s) after DOMContentLoaded');

	await page.evaluate(() => window.dispatchEvent(new Event('resize')));
	await page.waitForTimeout(100);
	const writes = await page.evaluate(() => window.__viewportWrites);
	assert(writes === 0, 'an unchanged resize rewrote the viewport meta ' + writes + ' time(s)');
});

// Village id from the Menu panel's build link: openWindow('build', {'vid':'<id>'}).
async function firstVillageId(page) {
	const link = page.locator('a#build[onclick]').first();
	await link.waitFor({ state: 'attached', timeout: 15000 });
	const onclick = await link.getAttribute('onclick');
	const match = /'vid'\s*:\s*'?(\d+)/.exec(onclick || '');
	assert(match, 'no village id in the Menu build link: ' + onclick);
	return match[1];
}

// Close the sheets the shell opens at start (such as "Welcome back"), so a check starts from an empty stack.
async function closeStartupSheets(page) {
	await page.waitForTimeout(1000);
	for (let i = 0; i < 10 && await page.locator('.sheet').count() > 0; i++) {
		await page.locator('.sheet:visible .sheet-close:visible').first().click();
	}
	assert(await page.locator('.sheet').count() === 0, 'could not close the start-up sheets');
}

// Open a window and wait until its sheet (one more than before) is on the page.
async function openSheet(page, windowId, requestData) {
	const before = await page.locator('.sheet').count();
	await page.evaluate(([id, data]) => openWindow(id, data), [windowId, requestData]);
	await page.waitForFunction((n) => document.querySelectorAll('.sheet').length > n, before, { timeout: 15000 });
}

const visibleSheets = (page) => page.locator('.sheet:visible');

check('sheets-open', { viewport: { width: 390, height: 844 }, layout: 'mobile' }, async (page) => {
	const vid = await firstVillageId(page);
	await closeStartupSheets(page);
	await openSheet(page, 'build', { vid });
	assert(await page.locator('.sheet').count() === 1, 'expected one .sheet, got ' + await page.locator('.sheet').count());
	const title = (await page.locator('.sheet .sheet-title').first().textContent() || '').trim();
	assert(title.length > 0, 'the sheet has no title text');
	assert(await page.locator('.dialog').count() === 0, 'unexpected .dialog element on the mobile layout');
});

check('sheets-stack', { viewport: { width: 390, height: 844 }, layout: 'mobile' }, async (page) => {
	const vid = await firstVillageId(page);
	await closeStartupSheets(page);
	await openSheet(page, 'build', { vid });
	await openSheet(page, 'economy', { vid });
	const sheets = page.locator('.sheet');
	assert(await sheets.count() === 2, 'expected two .sheet elements, got ' + await sheets.count());
	assert(await visibleSheets(page).count() === 1, 'expected one visible sheet, got ' + await visibleSheets(page).count());
	assert(await sheets.nth(1).isVisible(), 'the last sheet is not the visible one');

	const openBefore = await page.evaluate(() => Game.gui.aOpenWindows.length);
	await sheets.nth(1).locator('.sheet-back').click();
	await page.waitForFunction(() => document.querySelectorAll('.sheet').length === 1, null, { timeout: 5000 });
	assert(await sheets.first().isVisible(), 'the first sheet is not visible after going back');
	const openAfter = await page.evaluate(() => Game.gui.aOpenWindows.length);
	assert(openAfter === openBefore - 1, 'aOpenWindows went from ' + openBefore + ' to ' + openAfter + ', expected one less');
});

check('sheets-modal', { viewport: { width: 390, height: 844 }, layout: 'mobile' }, async (page) => {
	const vid = await firstVillageId(page);
	await closeStartupSheets(page);
	await openSheet(page, 'build', { vid });
	await page.evaluate(() => {
		const entry = Game.gui.aOpenWindows.filter((w) => w.sWindowId.toLowerCase() === 'build')[0];
		Game.gui.dialogMessage(entry, 'text', 'Yes', 'void(0)', 'No', 'void(0)');
	});
	const modal = page.locator('.sheet.sheet-modal');
	await modal.waitFor({ state: 'visible', timeout: 5000 });
	assert(await page.locator('.sheet-backdrop:visible').count() === 1, 'no visible .sheet-backdrop under the modal');
	const sheet = page.locator('.sheet:not(.sheet-modal)');
	assert(await sheet.count() === 1 && await sheet.first().isVisible(), 'the sheet under the modal is not visible');

	await modal.locator('.sheet-content button').nth(1).click();
	await page.waitForFunction(() => document.querySelectorAll('.sheet-modal, .sheet-backdrop').length === 0, null, { timeout: 5000 });
	assert(await sheet.count() === 1 && await sheet.first().isVisible(), 'the sheet is gone after closing the modal');
});

// Where the economy window's content element is: inside a .sheet-content, inside a .dialog, or missing.
async function economyPlace(page) {
	return page.evaluate(() => {
		const entry = Game.gui.aOpenWindows.filter((w) => w.sWindowId.toLowerCase() === 'economy')[0];
		if (!entry) {
			return 'no economy entry';
		}
		const found = [];
		const all = document.querySelectorAll('.guiwindow');
		for (let i = 0; i < all.length; i++) {
			if (all[i].dialogId === entry.sDialogId) {
				found.push(all[i]);
			}
		}
		if (found.length !== 1 || found[0] !== entry.div || entry.div.childNodes.length === 0) {
			return 'economy content element not found';
		}
		if (entry.div.closest('.sheet .sheet-content')) {
			return 'sheet';
		}
		if (entry.div.closest('.dialog')) {
			return 'dialog';
		}
		return 'elsewhere';
	});
}

check('relayout', { viewport: { width: 1280, height: 800 }, layout: 'auto' }, async (page) => {
	const vid = await firstVillageId(page);
	assert(await page.locator('body.layout-desktop').count() === 1, 'body has no layout-desktop class to start with');

	// Close the Prototype windows the shell opens at start (such as "Welcome back").
	await page.waitForTimeout(1000);
	await page.evaluate(() => {
		const windows = Game.gui.aOpenWindows.filter((w) => w.oWindow);
		for (let i = 0; i < windows.length; i++) {
			Game.gui.destroyWindow(windows[i]);
		}
	});
	assert(await page.locator('.dialog').count() === 0, 'could not close the start-up windows');

	await page.evaluate((v) => openWindow('economy', { vid: v }), vid);
	await page.waitForFunction(() => document.querySelectorAll('.dialog').length === 1, null, { timeout: 15000 });
	assert(await economyPlace(page) === 'dialog', 'economy is not in a .dialog: ' + await economyPlace(page));
	const openBefore = await page.evaluate(() => Game.gui.aOpenWindows.map((w) => w.sDialogId).join(','));

	await page.setViewportSize({ width: 390, height: 844 });
	await page.waitForSelector('body.layout-mobile', { timeout: 5000 });
	assert(await page.locator('.sheet').count() === 1, 'expected one .sheet, got ' + await page.locator('.sheet').count());
	assert(await page.locator('.dialog').count() === 0, 'expected no .dialog, got ' + await page.locator('.dialog').count());
	assert(await economyPlace(page) === 'sheet', 'economy is not in the sheet: ' + await economyPlace(page));
	let openNow = await page.evaluate(() => Game.gui.aOpenWindows.map((w) => w.sDialogId).join(','));
	assert(openNow === openBefore, 'open windows changed from [' + openBefore + '] to [' + openNow + ']');

	await page.setViewportSize({ width: 1280, height: 800 });
	await page.waitForSelector('body.layout-desktop', { timeout: 5000 });
	assert(await page.locator('.dialog').count() === 1, 'expected one .dialog, got ' + await page.locator('.dialog').count());
	assert(await page.locator('.sheet').count() === 0, 'expected no .sheet, got ' + await page.locator('.sheet').count());
	assert(await economyPlace(page) === 'dialog', 'economy is not in a .dialog: ' + await economyPlace(page));
	openNow = await page.evaluate(() => Game.gui.aOpenWindows.map((w) => w.sDialogId).join(','));
	assert(openNow === openBefore, 'open windows changed from [' + openBefore + '] to [' + openNow + ']');
});

// Map scale: +/- through Game.map.setScale (clamped, stored per browser and restored on reload) and two-finger pinch.
check('map-scale', { viewport: { width: 390, height: 844 }, layout: 'mobile' }, async (page) => {
	await closeStartupSheets(page);
	const n1 = await page.evaluate(() => Game.map.oScrollMap.countLoadedImages);
	await page.evaluate(() => Game.map.setScale(0.5));
	const after = await page.evaluate(() => ({
		transform: Game.map.oScrollMap.div.style.transform,
		count: Game.map.oScrollMap.countLoadedImages,
		stored: localStorage.getItem('dolumar.mapScale'),
	}));
	assert(after.transform.indexOf('scale(0.5)') >= 0, 'inner map div transform is "' + after.transform + '", expected scale(0.5)');
	assert(after.count > n1, 'countLoadedImages did not grow at scale 0.5: ' + n1 + ' -> ' + after.count);
	assert(after.stored === '0.5', 'stored scale is ' + JSON.stringify(after.stored) + ', expected "0.5"');

	await page.evaluate(() => Game.map.setScale(3));
	const clamped = await page.evaluate(() => Game.map.oScrollMap.getScale());
	assert(clamped === 1.5, 'setScale(3) gave scale ' + clamped + ', expected 1.5');

	// The stored scale comes back after a reload (mobile mode only).
	await page.reload({ waitUntil: 'load' });
	await page.waitForFunction(() => typeof Game !== 'undefined' && Game.core && Game.core.isMapLoaded && Game.map.oScrollMap, null, { timeout: 15000 });
	const restored = await page.evaluate(() => Game.map.oScrollMap.getScale());
	assert(restored === 1.5, 'scale after reload is ' + restored + ', expected 1.5');

	// Two-finger pinch on #map: fingers 100px apart, then 200px apart, then lifted.
	await page.evaluate(() => Game.map.setScale(1));
	const pinched = await page.evaluate(() => {
		const map = document.getElementById('map');
		const touch = (id, x, y) => new Touch({ identifier: id, target: map, clientX: x, clientY: y });
		const fire = (type, touches, changed) => map.dispatchEvent(new TouchEvent(type, {
			bubbles: true, cancelable: true, touches, targetTouches: touches, changedTouches: changed,
		}));
		const start = [touch(1, 145, 400), touch(2, 245, 400)];
		fire('touchstart', start, start);
		const moved = [touch(1, 95, 400), touch(2, 295, 400)];
		fire('touchmove', moved, moved);
		fire('touchend', [], moved);
		return { scale: Game.map.oScrollMap.getScale(), stored: localStorage.getItem('dolumar.mapScale') };
	});
	await page.evaluate(() => Game.map.setScale(1));
	assert(pinched.scale > 1, 'pinch did not grow the scale: ' + pinched.scale);
	assert(pinched.stored === String(pinched.scale), 'pinch did not store the scale: ' + JSON.stringify(pinched.stored));

	// An OS-cancelled gesture (touchcancel) resets the pinch and the drag, like touchend.
	const cancelled = await page.evaluate(() => {
		const map = document.getElementById('map');
		const o = Game.map.oScrollMap;
		const touch = (id, x, y) => new Touch({ identifier: id, target: map, clientX: x, clientY: y });
		const fire = (type, touches, changed) => map.dispatchEvent(new TouchEvent(type, {
			bubbles: true, cancelable: true, touches, targetTouches: touches, changedTouches: changed,
		}));
		const two = [touch(1, 145, 400), touch(2, 245, 400)];
		fire('touchstart', two, two);
		const pinchStarted = o.pinching === true;
		fire('touchcancel', [], two);
		const pinchAfter = o.pinching;
		const one = [touch(1, 195, 400)];
		fire('touchstart', one, one);
		const dragStarted = o.dragging === true;
		fire('touchcancel', [], one);
		return { pinchStarted, pinchAfter, dragStarted, dragAfter: o.dragging };
	});
	await page.evaluate(() => Game.map.setScale(1));
	assert(cancelled.pinchStarted && cancelled.dragStarted, 'the test gestures did not start: ' + JSON.stringify(cancelled));
	assert(!cancelled.pinchAfter, 'touchcancel left pinching = true');
	assert(!cancelled.dragAfter, 'touchcancel left dragging = true');
});

// Placement (mobile): start choosing a location from the build sheet's first build link.
async function startPlacement(page) {
	const vid = await firstVillageId(page);
	await closeStartupSheets(page);
	await openSheet(page, 'build', { vid });
	const link = page.locator('.sheet:visible a[onclick*="selectBuildLocation"], .sheet:visible a[onclick*="selectLocation"]').first();
	await link.waitFor({ state: 'visible', timeout: 15000 });
	await link.click();
	await page.locator('#map_select_actions').waitFor({ state: 'visible', timeout: 5000 });
	assert(await visibleSheets(page).count() === 0, 'a sheet is still visible while choosing a location');
	assert(!(await page.locator('#sheets').isVisible()), '#sheets is visible while choosing a location');
}

// Count calls of the map's enableControls from now on (each call adds another set of map listeners).
const countEnableControls = (page) => page.evaluate(() => {
	const map = Game.map.oScrollMap;
	const original = map.enableControls;
	window.__enableControlsCalls = 0;
	map.enableControls = function () {
		window.__enableControlsCalls++;
		return original.apply(this, arguments);
	};
});

// Elements on the page with an inline z-index of 1000000 (the map's blocking overlay).
// (A plain loop: Prototype.js replaces Array.prototype.filter with a version that needs an Array.)
const overlayCount = (page) => page.evaluate(() => {
	const all = document.querySelectorAll('div');
	let n = 0;
	for (let i = 0; i < all.length; i++) {
		if (String(all[i].style.zIndex) === '1000000') {
			n++;
		}
	}
	return n;
});

check('place-cancel', { viewport: { width: 390, height: 844 }, layout: 'mobile' }, async (page) => {
	await countEnableControls(page);
	await startPlacement(page);

	// Button text comes from the Menu panel's translated data-label-* attributes.
	const labels = await page.evaluate(() => ({
		cancel: document.querySelector('#map_select_actions button.cancel').textContent,
		place: document.querySelector('#map_select_actions button.place').textContent,
		expectCancel: document.getElementById('menu').getAttribute('data-label-selectcancel'),
		expectPlace: document.getElementById('menu').getAttribute('data-label-selectplace'),
	}));
	assert(labels.expectCancel && labels.expectPlace, 'the Menu panel has no data-label-selectcancel/selectplace');
	assert(labels.cancel === labels.expectCancel && labels.place === labels.expectPlace,
		'place/cancel buttons are not translated: ' + JSON.stringify(labels));

	await page.locator('#map_select_actions button.cancel').click();
	await page.waitForFunction(() => !document.getElementById('map_select_actions'), null, { timeout: 5000 });
	assert(await page.locator('#sheets').isVisible(), '#sheets is not visible after cancel');
	assert(await visibleSheets(page).count() === 1, 'the build sheet is not visible after cancel');
	assert(await overlayCount(page) === 0, 'an overlay with z-index 1000000 is left on the page');
	assert(!(await page.evaluate(() => Game.map.oScrollMap.selecting)), 'a selection is still in progress');
	const calls = await page.evaluate(() => window.__enableControlsCalls);
	assert(calls === 0, 'cancel re-enabled the map controls ' + calls + ' time(s) (duplicate listeners)');
});

check('place-confirm', { viewport: { width: 390, height: 844 }, layout: 'mobile' }, async (page) => {
	await countEnableControls(page);
	await startPlacement(page);

	// Pan the map with a 60px one-finger drag.
	const before = await page.evaluate(() => Game.map.oScrollMap.div.style.left);
	await page.evaluate(async () => {
		const map = document.getElementById('map');
		const touch = (x, y) => new Touch({ identifier: 1, target: map, clientX: x, clientY: y });
		const fire = (type, touches, changed) => map.dispatchEvent(new TouchEvent(type, {
			bubbles: true, cancelable: true, touches, targetTouches: touches, changedTouches: changed,
		}));
		const wait = (ms) => new Promise((r) => setTimeout(r, ms));
		const start = [touch(195, 400)];
		fire('touchstart', start, start);
		await wait(80);
		const moved = [touch(255, 400)];
		fire('touchmove', moved, moved);
		await wait(150);
		fire('touchend', [], moved);
		await wait(100);
	});
	const after = await page.evaluate(() => Game.map.oScrollMap.div.style.left);
	assert(before !== after, 'the touch drag did not pan the map (left stayed ' + before + ')');
	assert(await page.locator('#map_select_actions').isVisible(), 'the place/cancel bar is gone after panning');

	const request = page.waitForRequest((r) => r.method() === 'POST' && /\/dialog\//.test(r.url())
		&& /"x":/.test(r.postData() || '') && /"y":/.test(r.postData() || ''), { timeout: 10000 });
	await page.locator('#map_select_actions button.place').click();
	await request;
	await page.waitForFunction(() => !document.getElementById('map_select_actions'), null, { timeout: 5000 });
	await visibleSheets(page).first().waitFor({ state: 'visible', timeout: 10000 });
	assert(await page.locator('#sheets').isVisible(), '#sheets is not visible after placing');
	assert(await overlayCount(page) === 0, 'an overlay with z-index 1000000 is left on the page');
	assert(!(await page.evaluate(() => Game.map.oScrollMap.selecting)), 'a selection is still in progress');
	const calls = await page.evaluate(() => window.__enableControlsCalls);
	assert(calls === 0, 'place re-enabled the map controls ' + calls + ' time(s) (duplicate listeners)');
});

// A clean tap on the map must reach the clickable objects (image-map areas) as a click even
// though the map's touchstart handler cancels the default action; a drag must not.
check('map-tap', { viewport: { width: 390, height: 844 }, layout: 'mobile' }, async (page) => {
	await closeStartupSheets(page);
	const x = 200, y = 450;
	await page.evaluate(([x, y]) => {
		window.__mapClicks = [];
		const region = Array.from(Game.map.oScrollMap.div.querySelectorAll('div[id^="map"]')).filter((d) => {
			const b = d.getBoundingClientRect();
			return b.left <= x && b.right >= x && b.top <= y && b.bottom >= y;
		})[0];
		const map = document.createElement('map');
		map.id = map.name = 'smoke_probe_map';
		const area = document.createElement('area');
		area.shape = 'rect';
		area.coords = '0,0,' + parseInt(region.style.width) + ',' + parseInt(region.style.height);
		area.href = 'javascript:void(0);';
		area.observe('click', () => window.__mapClicks.push('area'));
		map.appendChild(area);
		region.appendChild(map);
		const img = document.createElement('img');
		img.src = CONFIG_GAMESERVER_URL + 'images/spacer.gif';
		img.style.cssText = 'position:absolute;left:0;top:0;width:' + region.style.width + ';height:' + region.style.height + ';z-index:10';
		img.useMap = '#smoke_probe_map';
		region.appendChild(img);
	}, [x, y]);

	await page.touchscreen.tap(x, y);
	await page.waitForTimeout(300);
	let clicks = await page.evaluate(() => window.__mapClicks);
	assert(clicks.length === 1, 'a tap on the map produced ' + clicks.length + ' click(s) on the area, expected 1');

	await page.evaluate(() => { window.__mapClicks = []; });
	await page.evaluate(async ([x, y]) => {
		const map = document.getElementById('map');
		const touch = (tx, ty) => new Touch({ identifier: 1, target: map, clientX: tx, clientY: ty });
		const fire = (type, touches, changed) => map.dispatchEvent(new TouchEvent(type, {
			bubbles: true, cancelable: true, touches, targetTouches: touches, changedTouches: changed,
		}));
		const wait = (ms) => new Promise((r) => setTimeout(r, ms));
		fire('touchstart', [touch(x, y)], [touch(x, y)]);
		await wait(80);
		fire('touchmove', [touch(x + 60, y)], [touch(x + 60, y)]);
		await wait(150);
		fire('touchend', [], [touch(x + 60, y)]);
		await wait(100);
	}, [x, y]);
	clicks = await page.evaluate(() => window.__mapClicks);
	assert(clicks.length === 0, 'a drag on the map produced ' + clicks.length + ' click(s), expected none');
});


// Mobile header and tab bar: Statusbar fixed at the top, Menu as a bottom tab bar with a generated "More" tab.
check('tabbar', { viewport: { width: 390, height: 844 }, layout: 'mobile' }, async (page) => {
	// The General chat the server opens at start is closed on mobile (Welcome stays).
	const chatOpen = () => page.evaluate(() => {
		let n = 0;
		for (let i = 0; i < Game.gui.aOpenWindows.length; i++) {
			if (Game.gui.aOpenWindows[i].sWindowId.toLowerCase() === 'chat') {
				n++;
			}
		}
		return n;
	});
	await page.waitForTimeout(1000);
	assert(await chatOpen() === 0, 'the start-up Chat window is still open on mobile');
	await closeStartupSheets(page);
	const menu = await page.locator('.topmenu').first().boundingBox();
	assert(menu, 'no .topmenu element on the page');
	assert(Math.round(menu.y + menu.height) === 844, '.topmenu bottom is ' + (menu.y + menu.height) + ', expected 844');
	assert(Math.round(menu.height) === 56, '.topmenu height is ' + menu.height + ', expected 56');
	const tabs = page.locator('.topmenu #menu-main > li:visible');
	assert(await tabs.count() === 6, 'expected six visible tabs, got ' + await tabs.count());
	// Header: village switcher and buttons on the first row, the resource strip on the second.
	const header = await page.locator('.mobile-header').first().boundingBox();
	assert(header && Math.round(header.y) === 0 && Math.round(header.height) === 48, '.mobile-header is ' + JSON.stringify(header) + ', expected 48px at the top');
	const resources = await page.locator('.mobile-resources').first().boundingBox();
	assert(resources && Math.round(resources.y) === 48 && Math.round(resources.height) === 24, '.mobile-resources is ' + JSON.stringify(resources) + ', expected 24px under the header');
	assert(await page.locator('.mobile-resources span.resource').count() > 0, 'the resource strip shows no resources');
	const village = (await page.locator('.mobile-village button.name').textContent() || '').trim();
	assert(village.length > 0, 'the header shows no village name');
	for (const label of ['Map', 'Chat', 'Inbox']) {
		assert(await page.locator('.mobile-header-actions button .label', { hasText: label }).count() === 1, 'no labelled ' + label + ' button in the header');
	}
	assert(await page.locator('.status').first().isHidden(), 'the Statusbar panel is visible on mobile');

	// Ticker: hidden while nothing is in progress, a row under the resources (and a sheet on tap) when something is.
	assert(await page.locator('.mobile-ticker').isHidden(), 'the ticker row is shown with nothing in progress');
	await page.evaluate(() => {
		const list = document.querySelector('.panel.status ul.statusbar');
		list.insert('<li class="construct">[<span class="counter">00:05:00</span>] Smoke farm <a class="speedup" href="javascript:void(0);">Hurry up!</a></li>');
		Game.dolumar.mobile.syncHeader();
	});
	const tickerBox = await page.locator('.mobile-ticker').boundingBox();
	assert(tickerBox && Math.round(tickerBox.y) === 72 && Math.round(tickerBox.height) === 22, '.mobile-ticker is ' + JSON.stringify(tickerBox) + ', expected 22px under the resources');
	assert((await page.locator('.mobile-ticker li').textContent() || '').indexOf('Smoke farm') >= 0, 'the ticker row does not show the counter');
	assert(await page.evaluate(() => getComputedStyle(Game.sheets.container()).top) === '94px', '#sheets top did not move under the ticker');
	await page.locator('.mobile-ticker').click({ position: { x: 300, y: 11 } });
	await page.locator('.sheet.mobile-ticker-sheet').waitFor({ state: 'visible', timeout: 5000 });
	assert((await page.locator('.sheet.mobile-ticker-sheet .sheet-title').textContent() || '').trim() === 'In progress', 'the ticker sheet has the wrong title');
	assert(await page.locator('.sheet.mobile-ticker-sheet a.speedup').count() === 1, 'the ticker sheet lost the speed-up link');
	await page.evaluate(() => {
		document.querySelector('.panel.status ul.statusbar li.construct').remove();
		Game.dolumar.mobile.syncHeader();
	});
	await page.waitForFunction(() => document.querySelectorAll('.sheet.mobile-ticker-sheet').length === 0, null, { timeout: 5000 });
	assert(await page.locator('.mobile-ticker').isHidden(), 'the ticker row stays shown after the counters are gone');
	assert(await page.evaluate(() => getComputedStyle(Game.sheets.container()).top) === '72px', '#sheets top did not move back');
	const sheets = await page.evaluate(() => {
		const style = getComputedStyle(Game.sheets.container());
		return { top: style.top, bottom: style.bottom };
	});
	assert(sheets.top === '72px', '#sheets top is ' + sheets.top + ', expected 72px');
	assert(sheets.bottom === '56px', '#sheets bottom is ' + sheets.bottom + ', expected 56px');

	await page.locator('.topmenu li.tab-more').click();
	const more = page.locator('.sheet:visible');
	await more.first().waitFor({ state: 'visible', timeout: 5000 });
	const title = (await more.first().locator('.sheet-title').textContent() || '').trim();
	assert(title === 'More', 'the visible sheet is titled "' + title + '", expected "More"');
	assert(await more.first().locator('.navigation.ranking').count() > 0, 'the More sheet has no ranking item');
	const guide = more.first().locator('.mobile-more-list > li.mobile-more-guide > a[title="Guide"]');
	assert(await guide.count() === 1, 'the More sheet has no Guide entry');
	const guideShown = () => page.evaluate(() => {
		const panel = document.querySelector('.panel.guide');
		return !!panel && getComputedStyle(panel).display !== 'none';
	});
	const guideBefore = await guideShown();
	await guide.click();
	await page.waitForFunction(() => document.querySelectorAll('.sheet').length === 0, null, { timeout: 5000 });
	assert(await guideShown() === !guideBefore, 'tapping Guide did not ' + (guideBefore ? 'hide' : 'show') + ' the Guide panel');
	await page.locator('.topmenu li.tab-more').click();
	await more.first().waitFor({ state: 'visible', timeout: 5000 });
	await more.first().locator('.sheet-close').click();
	await page.waitForFunction(() => document.querySelectorAll('.sheet').length === 0, null, { timeout: 5000 });

	await page.locator('.topmenu li[data-tab="build"] > a').click();
	await page.waitForFunction(() => document.querySelectorAll('.sheet').length === 1, null, { timeout: 15000 });

	// A root sheet: title and village subtitle, no Back, the Build tab marked, actions as buttons.
	const build = page.locator('.sheet:visible').first();
	assert((await build.locator('.sheet-title-text').textContent() || '').trim() === 'Build Buildings', 'the Build sheet title is not "Build Buildings"');
	assert((await build.locator('.sheet-subtitle').textContent() || '').trim().length > 0, 'the Build sheet has no village subtitle');
	assert(await build.locator('.sheet-back').isHidden(), 'Back is shown on a root sheet');
	await page.waitForFunction(() => document.querySelector('#menu-main li[data-tab="build"].active-tab') !== null, null, { timeout: 5000 });
	assert(await build.locator('a.action-button.primary').count() > 0, 'the Build sheet has no primary Construct button');
	if (await build.locator('p.hint-mobile, p.hint-desktop').count() > 0) {
		assert(await build.locator('p.hint-mobile').isVisible() && await build.locator('p.hint-desktop').isHidden(), 'the Build sheet does not show the touch hint only');
	}

	// A link inside a sheet stacks (Back appears); a tab tap starts a fresh stack.
	await build.locator('a.action-button', { hasText: 'Bonus Buildings' }).click();
	await page.waitForFunction(() => Game.sheets.stack.length === 2, null, { timeout: 15000 });
	assert(await page.locator('.sheet:visible .sheet-back').isVisible(), 'Back is hidden on a stacked sheet');
	await page.locator('.topmenu li[data-tab="logbook"] > a').click();
	await page.waitForFunction(() => Game.sheets.stack.length === 1 && Game.gui.aOpenWindows.filter(w => w.sWindowId.toLowerCase() === 'logbook').length === 1, null, { timeout: 15000 });
	assert(await page.locator('.sheet:visible .sheet-back').isHidden(), 'Back is shown after a tab tap reset the stack');
	await page.waitForFunction(() => document.querySelector('#menu-main li[data-tab="logbook"].active-tab') !== null, null, { timeout: 5000 });
});

// The header and tab bar lie above #sheets. A tab tap while a building is being placed cancels the
// placement (it does not close the build window under it); while a confirm is up, taps on them do
// nothing and the confirm stays on top; ticker links post to the Statusbar window; newsflashes show.
check('chrome-guards', { viewport: { width: 390, height: 844 }, layout: 'mobile' }, async (page) => {
	await startPlacement(page);
	await page.locator('.topmenu li[data-tab="logbook"] > a').click();
	await page.waitForFunction(() => !document.getElementById('map_select_actions') && !Game.map.oScrollMap.selecting, null, { timeout: 5000 });
	await page.waitForFunction(() => Game.gui.aOpenWindows.filter((w) => w.sWindowId.toLowerCase() === 'logbook').length === 1, null, { timeout: 15000 });
	assert(await page.locator('#sheets').isVisible(), '#sheets is hidden after a tab tap during placement');
	await page.waitForFunction(() => Game.sheets.stack.length === 1, null, { timeout: 5000 });

	const openConfirm = () => page.evaluate(() => {
		const entry = Game.gui.aOpenWindows.filter((w) => w.sWindowId.toLowerCase() === 'logbook')[0];
		Game.gui.dialogMessage(entry, 'text', 'Yes', 'void(0)', 'No', 'void(0)');
	});
	await openConfirm();
	const modal = page.locator('.sheet.sheet-modal');
	await modal.waitFor({ state: 'visible', timeout: 5000 });
	assert(await page.locator('body.has-modal-sheet').count() === 1, 'body has no has-modal-sheet class while a confirm is up');
	const stackBefore = await page.evaluate(() => Game.sheets.stack.length);
	await page.locator('.topmenu li[data-tab="build"] > a').click({ force: true });
	await page.locator('.mobile-header button.inbox').click({ force: true });
	await page.waitForTimeout(1500);
	assert(await page.evaluate(() => Game.sheets.stack.length) === stackBefore, 'a tap on the tab bar or header changed the sheets while a confirm was up');
	assert(await modal.isVisible(), 'the confirm is gone after a tap on the tab bar');

	// A sheet opened while the confirm is up opens under it.
	const vid = await firstVillageId(page);
	await page.evaluate((v) => openWindow('economy', { vid: v }), vid);
	await page.waitForFunction(() => Game.gui.aOpenWindows.filter((w) => w.sWindowId.toLowerCase() === 'economy').length === 1, null, { timeout: 15000 });
	await page.waitForTimeout(300);
	const last = await page.evaluate(() => {
		const children = document.getElementById('sheets').children;
		return children[children.length - 1].classList.contains('sheet-modal');
	});
	assert(last, 'a sheet opened after the confirm covers it');
	await modal.locator('.sheet-content button').nth(1).click();
	await page.waitForFunction(() => document.querySelectorAll('.sheet-modal').length === 0 && !document.body.classList.contains('has-modal-sheet'), null, { timeout: 5000 });

	// Ticker: the copied Statusbar list resolves to the Statusbar window (cancel and withdraw post there).
	await page.evaluate(() => {
		const list = document.querySelector('.panel.status ul.statusbar');
		list.insert('<li class="construct">[<span class="counter">00:05:00</span>] Smoke farm <a class="smoke-cancel" href="javascript:void(0);">Cancel</a></li>');
		Game.dolumar.mobile.syncHeader();
	});
	const owner = await page.evaluate(() => {
		const link = document.querySelector('.mobile-ticker a.smoke-cancel');
		const win = link ? Game.gui.getWindowFromElement(link) : false;
		return { link: !!link, win: win ? win.sDialogId : null, status: document.querySelector('.panel.status').dialogId };
	});
	assert(owner.link && owner.win !== null && owner.win === owner.status, 'a ticker link does not resolve to the Statusbar window: ' + JSON.stringify(owner));
	await page.evaluate(() => {
		document.querySelector('.panel.status ul.statusbar li.construct').remove();
		Game.dolumar.mobile.syncHeader();
	});

	// A newsflash scrolls over the resource strip (the Newsbar panel is hidden).
	await page.evaluate(() => {
		const entry = Game.gui.getWindowFromDialogId(document.querySelector('.panel.newsbar').dialogId);
		const content = document.createElement('div');
		content.textContent = 'Smoke news';
		Game.gui.showNewsflash(entry, content);
	});
	await page.locator('.mobile-newsflash .newsflash').waitFor({ state: 'visible', timeout: 5000 });
	await page.waitForFunction(() => (document.querySelector('.mobile-newsflash .newsflash') || {}).textContent === 'Smoke news', null, { timeout: 5000 });
	const flash = await page.locator('.mobile-newsflash .newsflash').boundingBox();
	assert(flash && Math.round(flash.y) === 48 && flash.width >= 380, 'the newsflash is not over the resource strip: ' + JSON.stringify(flash));
});

// The Map button: the minimap (created inside the hidden panel) gets its size and tiles, the open
// sheets close, and a sheet opened later covers the minimap. Header labels follow the Menu panel.
check('map-panels', { viewport: { width: 390, height: 844 }, layout: 'mobile' }, async (page) => {
	const vid = await firstVillageId(page);
	await closeStartupSheets(page);
	await openSheet(page, 'build', { vid });
	await page.locator('.mobile-header button.minimap').click();
	await page.waitForFunction(() => {
		const panel = document.querySelector('.panel.minimap');
		return panel && getComputedStyle(panel).display !== 'none';
	}, null, { timeout: 15000 });
	assert(await page.locator('.sheet').count() === 0, 'the Map button did not close the open sheets');
	await page.waitForFunction(() => Game.minimap.oScrollMap && Game.minimap.oScrollMap.displaySizeX > 1
		&& document.querySelectorAll('#minimap_real img').length > 0, null, { timeout: 10000 });
	assert(await page.locator('.mobile-header button.minimap.active').count() === 1, 'the Map button is not marked active');

	await openSheet(page, 'economy', { vid });
	const covered = await page.evaluate(() => {
		const box = document.querySelector('.panel.minimap').getBoundingClientRect();
		const hit = document.elementFromPoint(box.left + box.width / 2, box.top + box.height / 2);
		return !!hit && !!hit.closest('#sheets');
	});
	assert(covered, 'the minimap lies over the open sheet');
	// Map with a sheet over the visible minimap brings the minimap forward; a second tap hides it.
	await page.locator('.mobile-header button.minimap').click();
	await page.waitForFunction(() => document.querySelectorAll('.sheet').length === 0
		&& getComputedStyle(document.querySelector('.panel.minimap')).display !== 'none', null, { timeout: 5000 });
	await page.locator('.mobile-header button.minimap').click();
	await page.waitForFunction(() => getComputedStyle(document.querySelector('.panel.minimap')).display === 'none', null, { timeout: 5000 });

	// Labels: a new translation on the Menu panel reaches the header buttons.
	const label = await page.evaluate(() => {
		const menu = document.getElementById('menu');
		const old = menu.getAttribute('data-label-chat');
		menu.setAttribute('data-label-chat', 'Smoke chat');
		Game.dolumar.mobile.syncHeader();
		const button = document.querySelector('.mobile-header button.chat');
		const out = { text: button.querySelector('.label').textContent, aria: button.getAttribute('aria-label') };
		menu.setAttribute('data-label-chat', old);
		Game.dolumar.mobile.syncHeader();
		return out;
	});
	assert(label.text === 'Smoke chat' && label.aria === 'Smoke chat', 'the header did not take the Menu panel label: ' + JSON.stringify(label));
});

// Window content fits a 390px sheet: no page overflow, and the sheet content does not scroll sideways
// (wide tables are wrapped in a .table-scroll, which scrolls on its own).
check('content-fit', { viewport: { width: 390, height: 844 }, layout: 'mobile' }, async (page) => {
	const vid = await firstVillageId(page);
	await closeStartupSheets(page);
	const windows = [['build', { vid }], ['economy', { vid }], ['squads', { vid }], ['logbook', { village: vid }]];
	for (const [id, data] of windows) {
		await openSheet(page, id, data);
		const sheet = visibleSheets(page).first();
		await sheet.locator('.sheet-content > *').first().waitFor({ state: 'attached', timeout: 15000 });
		await page.waitForTimeout(500);
		const size = await page.evaluate(() => {
			const sheets = document.querySelectorAll('.sheet');
			const content = sheets[sheets.length - 1].querySelector('.sheet-content');
			return {
				page: document.documentElement.scrollWidth,
				scroll: content.scrollWidth,
				client: content.clientWidth,
			};
		});
		assert(size.page <= 390, id + ': document scrollWidth is ' + size.page + ', expected <= 390');
		assert(size.scroll <= size.client + 1, id + ': .sheet-content scrollWidth ' + size.scroll + ' > clientWidth ' + size.client);
		await sheet.locator('.sheet-close').click();
		await page.waitForFunction(() => document.querySelectorAll('.sheet').length === 0, null, { timeout: 5000 });
	}

	// A wide table that arrives through a content update is wrapped once, however often the hook runs.
	await openSheet(page, 'economy', { vid });
	await visibleSheets(page).first().locator('.sheet-content table').first().waitFor({ state: 'attached', timeout: 15000 });
	const wrap = await page.evaluate(() => {
		const sheets = document.querySelectorAll('.sheet');
		const content = sheets[sheets.length - 1].querySelector('.sheet-content');
		const holder = document.createElement('div');
		holder.innerHTML = '<table class="smoke-wide"><tr>' + '<td style="white-space:nowrap">wide-column-content-xxxxx</td>'.repeat(6) + '</tr></table>';
		content.appendChild(holder);
		Game.gui.onUpdateContent(content);
		Game.gui.onUpdateContent(content);
		const table = content.querySelector('table.smoke-wide');
		const result = {
			wrappers: holder.querySelectorAll('.table-scroll').length,
			parent: table.parentNode.className,
			scroll: content.scrollWidth,
			client: content.clientWidth,
			page: document.documentElement.scrollWidth,
		};
		holder.remove();
		return result;
	});
	assert(wrap.wrappers === 1 && wrap.parent === 'table-scroll', 'wide table: expected one .table-scroll around it, got ' + JSON.stringify(wrap));
	assert(wrap.scroll <= wrap.client + 1 && wrap.page <= 390, 'wide table: the sheet overflows: ' + JSON.stringify(wrap));

	// Tap tooltip: a tap on a titled icon shows one tooltip with its title, a second tap removes it.
	const icon = visibleSheets(page).first().locator('.sheet-content span.resource[title]').first();
	const iconTitle = await icon.getAttribute('title');
	await icon.tap();
	const tips = await page.locator('.tap-tooltip').allTextContents();
	assert(tips.length === 1 && tips[0] === iconTitle, 'tooltip: expected one .tap-tooltip "' + iconTitle + '", got ' + JSON.stringify(tips));
	await icon.tap();
	assert(await page.locator('.tap-tooltip').count() === 0, 'tooltip: still shown after a second tap');
	await visibleSheets(page).first().locator('.sheet-close').click();
	await page.waitForFunction(() => document.querySelectorAll('.sheet').length === 0, null, { timeout: 5000 });

	// 44px tap targets: a list link (squads actions) and a submit button (battle simulator).
	const tall = async (locator, what) => {
		await locator.waitFor({ state: 'visible', timeout: 15000 });
		const box = await locator.boundingBox();
		assert(box && box.height >= 44, what + ' is ' + (box && box.height) + 'px tall, expected >= 44');
	};
	await openSheet(page, 'squads', { vid });
	await tall(visibleSheets(page).first().locator('.sheet-content li > a').first(), 'squads list link');
	await visibleSheets(page).first().locator('.sheet-close').click();
	await page.waitForFunction(() => document.querySelectorAll('.sheet').length === 0, null, { timeout: 5000 });
	await openSheet(page, 'Simulator', {});
	await tall(visibleSheets(page).first().locator('.sheet-content button[type=submit], .sheet-content input[type=submit]').first(), 'simulator submit button');
});

async function main() {
	const args = process.argv.slice(2);
	const baseUrl = args.find((a) => !a.startsWith('--'));
	const onlyArg = args.find((a) => a.startsWith('--only='));
	if (!baseUrl) {
		console.error('Usage: node tools/mobile-smoke.js <baseUrl> [--only=<check>[,<check>]]');
		return 2;
	}

	let selected = checks;
	if (onlyArg) {
		const names = onlyArg.slice('--only='.length).split(',').filter(Boolean);
		const unknown = names.filter((n) => !checks.some((c) => c.name === n));
		if (unknown.length > 0) {
			console.error('Unknown check(s): ' + unknown.join(', ') + '. Available: ' + checks.map((c) => c.name).join(', '));
			return 2;
		}
		selected = checks.filter((c) => names.includes(c.name));
	}

	const browser = await playwright.chromium.launch();
	let failed = 0;
	for (const { name, options, fn } of selected) {
		const narrow = options.viewport.width < 800;
		const context = await browser.newContext({
			viewport: options.viewport,
			hasTouch: narrow,
			isMobile: narrow,
		});
		context.setDefaultTimeout(30000);
		const page = await context.newPage();
		page.__layout = options.layout;
		if (options.recordViewport) {
			// Viewport meta content at DOMContentLoaded, and the number of writes to it after that.
			await page.addInitScript(() => {
				document.addEventListener('DOMContentLoaded', () => {
					const meta = document.getElementById('viewport');
					window.__viewportAtDomReady = meta ? meta.getAttribute('content') : null;
					window.__viewportWrites = 0;
					if (meta) {
						new MutationObserver((records) => { window.__viewportWrites += records.length; })
							.observe(meta, { attributes: true, attributeFilter: ['content'] });
					}
				});
			});
		}
		try {
			await login(page, baseUrl);
			await fn(page);
			console.log('PASS ' + name);
		} catch (e) {
			failed++;
			console.log('FAIL ' + name + ': ' + e.message.split('\n')[0]);
			await page.screenshot({ path: '/tmp/mobile-smoke-' + name + '.png' }).catch(() => {});
		}
		await context.close();
	}
	await browser.close();
	return failed > 0 ? 1 : 0;
}

main().then((code) => process.exit(code), (e) => {
	console.error(e);
	process.exit(1);
});

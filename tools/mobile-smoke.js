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
async function login(page, baseUrl, user = 1) {
	const start = new URL('/', baseUrl);
	if (user > 1) {
		// The mock reads the player number from this cookie; set it here for the mock's host (MOBILE_SMOKE_OIDC_URL).
		const mock = process.env.MOBILE_SMOKE_OIDC_URL || 'http://localhost:8090';
		await page.context().addCookies([{ name: 'oidc_mock_user', value: String(user), url: mock }]);
	}
	if (page.__layout) {
		start.searchParams.set('layout', page.__layout);
	}
	await page.goto(start.toString(), { waitUntil: 'load' });
	await page.waitForFunction(() => typeof CONFIG_GAME_NAME !== 'undefined', null, { timeout: 15000 });

	const nickname = page.locator('input[name=username]');
	if (await nickname.first().waitFor({ state: 'visible', timeout: 8000 }).then(() => true).catch(() => false)) {
		await nickname.first().fill('Smoke' + user + String(Date.now()).slice(-5));
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

check('layout-toggle', { viewport: { width: 1280, height: 800 } }, async (page) => {
	const baseUrl = page.url().split('?')[0];
	const select = () => page.locator('select[name=layout]').first();
	const openAccount = async () => {
		await page.evaluate(() => openWindow('MyAccount'));
		await select().waitFor({ state: 'visible', timeout: 15000 });
	};

	assert(await page.locator('body.layout-desktop').count() === 1, 'body has no layout-desktop class to start with');

	await openAccount();
	await select().selectOption('mobile');
	await page.waitForSelector('body.layout-mobile', { timeout: 2000 });

	// The preference is saved on the server: a plain reload (no ?layout=) stays mobile.
	await page.waitForTimeout(1000);
	await page.goto(baseUrl, { waitUntil: 'load' });
	await page.waitForSelector('body.layout-mobile', { timeout: 15000 });

	// Back to desktop (always leave the mock player on desktop).
	await page.setViewportSize({ width: 1280, height: 800 });
	await page.evaluate(() => openWindow('MyAccount'));
	await select().waitFor({ state: 'visible', timeout: 15000 });
	await select().selectOption('desktop');
	await page.waitForSelector('body.layout-desktop', { timeout: 2000 });
	await page.waitForTimeout(1000);
	await page.goto(baseUrl, { waitUntil: 'load' });
	await page.waitForSelector('body.layout-desktop', { timeout: 15000 });
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
	const status = await page.locator('.status').first().boundingBox();
	assert(status && Math.round(status.y) === 0, '.status top is ' + (status && status.y) + ', expected 0');
	const sheets = await page.evaluate(() => {
		const style = getComputedStyle(Game.sheets.container());
		return { top: style.top, bottom: style.bottom };
	});
	assert(sheets.top === '44px', '#sheets top is ' + sheets.top + ', expected 44px');
	assert(sheets.bottom === '56px', '#sheets bottom is ' + sheets.bottom + ', expected 56px');

	await page.locator('.topmenu li.tab-more').click();
	const more = page.locator('.sheet:visible');
	await more.first().waitFor({ state: 'visible', timeout: 5000 });
	const title = (await more.first().locator('.sheet-title').textContent() || '').trim();
	assert(title === 'More', 'the visible sheet is titled "' + title + '", expected "More"');
	assert(await more.first().locator('.navigation.ranking').count() > 0, 'the More sheet has no ranking item');
	const guide = more.first().locator('.mobile-more-list > li.mobile-more-guide > a[title="Guide"]');
	assert(await guide.count() === 1, 'the More sheet has no Guide entry');
	await guide.click();
	await page.waitForFunction(() => document.querySelectorAll('.sheet').length === 0, null, { timeout: 5000 });
	assert(await page.locator('.panel.guide.mobile-visible .icon a').isVisible(), 'the Guide panel is not visible after tapping Guide');
	await page.locator('.topmenu li.tab-more').click();
	await more.first().waitFor({ state: 'visible', timeout: 5000 });
	await more.first().locator('.sheet-close').click();
	await page.waitForFunction(() => document.querySelectorAll('.sheet').length === 0, null, { timeout: 5000 });

	await page.locator('.topmenu li[data-tab="build"] > a').click();
	await page.waitForFunction(() => document.querySelectorAll('.sheet').length === 1, null, { timeout: 15000 });
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

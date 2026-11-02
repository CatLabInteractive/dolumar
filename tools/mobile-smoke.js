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

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
 * Race and location selection is not done.
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

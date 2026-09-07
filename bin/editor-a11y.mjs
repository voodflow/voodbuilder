/**
 * Accessible-name audit for the editor chrome.
 *
 * Every control an author can operate must announce what it changes. This is easy to
 * regress silently here for two reasons: the inspector is built by imperative DOM code
 * rather than templates, so there is no markup to review; and enhanced selects hide the
 * native control behind a button, which quietly detaches any `label[for]` from the thing
 * the user actually clicks.
 *
 * The run selects a component and visits every right-panel tab, because most controls do
 * not exist until something is selected — a boot-only check would audit an empty panel and
 * pass.
 *
 * Playwright is not a project dependency — run this from an environment that provides it
 * (`npx playwright install chromium`, or point VOODBUILDER_CHROMIUM at an existing build).
 *
 * Usage:
 *   node packages/voodflow/voodbuilder/bin/editor-a11y.mjs [url]
 *
 * Env:
 *   VOODBUILDER_CHROMIUM        reuse an already-downloaded Chromium binary
 *   VOODBUILDER_SMOKE_EMAIL     builder account (default admin@cosmolab.test)
 *   VOODBUILDER_SMOKE_PASSWORD  builder password (default password)
 */
import { chromium } from 'playwright';

const url = process.argv[2] ?? 'http://localhost:8010/?locale=en&edit=1';
const browser = await chromium.launch(
    process.env.VOODBUILDER_CHROMIUM
        ? { executablePath: process.env.VOODBUILDER_CHROMIUM }
        : {},
);
const context = await browser.newContext({ viewport: { width: 1600, height: 1000 } });
const page = await context.newPage();

const errors = [];
page.on('pageerror', (error) => errors.push('pageerror: ' + String(error).slice(0, 300)));

const origin = new URL(url).origin;

await page.goto(origin + '/admin/login', { waitUntil: 'domcontentloaded', timeout: 60_000 });
await page.fill('input[type="email"], input[id$="email"]', process.env.VOODBUILDER_SMOKE_EMAIL ?? 'admin@cosmolab.test');
await page.fill('input[type="password"], input[id$="password"]', process.env.VOODBUILDER_SMOKE_PASSWORD ?? 'password');
await Promise.all([
    page.waitForNavigation({ waitUntil: 'domcontentloaded', timeout: 60_000 }).catch(() => {}),
    page.click('button[type="submit"]'),
]);

await page.goto(url, { waitUntil: 'domcontentloaded', timeout: 60_000 });
await page.waitForFunction(
    () => document.querySelector('[data-voodbuilder-boot-overlay]')?.hidden === true
        && Boolean(document.querySelector('.gjs-frame, .gjs-cv-canvas iframe')),
    null,
    { timeout: 90_000 },
);
await page.waitForTimeout(2_500);
errors.length = 0;

// Most inspector controls only exist for a selected component.
await page
    .frameLocator('.gjs-frame')
    .first()
    .locator('section p, section h2, section h1, section a')
    .first()
    .click({ timeout: 20_000 });
await page.waitForTimeout(1_500);

const auditPanels = () => page.evaluate(() => {
    const root = document.querySelector('.voodbuilder-editor-root');

    if (! root) {
        return null;
    }

    /** Simplified accessible name computation, in specification order. */
    const nameOf = (el) => {
        const labelledBy = el.getAttribute('aria-labelledby');

        if (labelledBy) {
            const text = labelledBy
                .split(/\s+/)
                .map((id) => document.getElementById(id)?.textContent?.trim() ?? '')
                .filter(Boolean)
                .join(' ');

            if (text) {
                return text;
            }
        }

        const ariaLabel = el.getAttribute('aria-label')?.trim();

        if (ariaLabel) {
            return ariaLabel;
        }

        if (el.id) {
            const text = document
                .querySelector(`label[for="${CSS.escape(el.id)}"]`)
                ?.textContent
                ?.trim();

            if (text) {
                return text;
            }
        }

        const wrapping = el.closest('label')?.textContent?.trim();

        if (wrapping) {
            return wrapping;
        }

        return el.getAttribute('title')?.trim() || null;
    };

    const describe = (el) => {
        const parts = [];
        let node = el;

        for (let depth = 0; node && depth < 4; depth += 1) {
            const cls = String(node.className || '').split(/\s+/).filter(Boolean)[0];
            parts.unshift(cls ? `${node.tagName.toLowerCase()}.${cls}` : node.tagName.toLowerCase());
            node = node.parentElement;
        }

        return parts.join(' > ');
    };

    const unnamed = [];
    let audited = 0;

    for (const el of root.querySelectorAll(
        'input:not([type="hidden"]), select, textarea, [role="textbox"], .voodbuilder-editor-select-trigger',
    )) {
        // A hidden native select is deliberately ignored by assistive tech; the trigger in
        // front of it is the control that has to carry the name.
        if (el.getAttribute('aria-hidden') === 'true') {
            continue;
        }

        // Author content rendered inside the shell is the page, not the editor UI.
        if (el.closest('.gjs-cv-canvas, .voodbuilder-editor-container')) {
            continue;
        }

        // Controls inside a collapsed sector or a closed modal are not filtered out: they
        // are one click away, and skipping them is how the Style panel went unaudited.
        audited += 1;

        if (nameOf(el) === null) {
            unnamed.push({ tag: el.tagName.toLowerCase(), where: describe(el) });
        }
    }

    return { audited, unnamed };
});

const tabs = await page.$$('.voodbuilder-editor-shell__right [role="tab"], .voodbuilder-editor-panel-tabs button');
const snapshots = [{ tab: 'initial', ...(await auditPanels()) }];

for (let index = 0; index < tabs.length; index += 1) {
    await tabs[index].click().catch(() => {});
    await page.waitForTimeout(1_200);
    snapshots.push({ tab: `tab-${index}`, ...(await auditPanels()) });
}

const unnamed = [];
const seen = new Set();

for (const snapshot of snapshots) {
    for (const control of snapshot.unnamed ?? []) {
        const key = `${control.tag}|${control.where}`;

        if (! seen.has(key)) {
            seen.add(key);
            unnamed.push(control);
        }
    }
}

console.log(JSON.stringify({
    url,
    tabsVisited: snapshots.length,
    controlsAudited: Math.max(...snapshots.map((snapshot) => snapshot.audited ?? 0)),
    unnamed,
    errors,
}, null, 2));

await browser.close();

process.exit(unnamed.length === 0 && errors.length === 0 ? 0 : 1);

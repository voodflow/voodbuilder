/**
 * Editor boot smoke test.
 *
 * Verifies that the code-split editor still boots: splash dismissed, block catalog
 * populated, no console errors, and that heavy chunks stay off the boot path.
 *
 * Playwright is not a project dependency — run this from an environment that provides it
 * (`npx playwright install chromium`, or point VOODBUILDER_CHROMIUM at an existing build).
 *
 * Usage:
 *   node packages/voodflow/voodbuilder/bin/editor-smoke.mjs [url]
 *
 * Env:
 *   VOODBUILDER_CHROMIUM        reuse an already-downloaded Chromium binary
 *   VOODBUILDER_SMOKE_EMAIL     builder account (default admin@cosmolab.test)
 *   VOODBUILDER_SMOKE_PASSWORD  builder password (default password)
 */
import { chromium } from 'playwright';

const url = process.argv[2] ?? 'http://localhost:8010/?locale=en&edit=1';
// Allow reusing an already-downloaded Chromium when the local Playwright build
// revision does not match the browser cache.
const browser = await chromium.launch(
    process.env.VOODBUILDER_CHROMIUM
        ? { executablePath: process.env.VOODBUILDER_CHROMIUM }
        : {},
);
const context = await browser.newContext({ viewport: { width: 1600, height: 1000 } });
const page = await context.newPage();

const errors = [];
const requestedScripts = [];

page.on('console', (message) => {
    if (message.type() === 'error') {
        errors.push(message.text().slice(0, 300));
    }
});
page.on('pageerror', (error) => errors.push('pageerror: ' + String(error).slice(0, 300)));
page.on('request', (request) => {
    if (request.resourceType() === 'script') {
        requestedScripts.push(request.url().split('/').pop());
    }
});

// The editor only mounts for a user holding page-builder access, so sign in first.
const origin = new URL(url).origin;
const email = process.env.VOODBUILDER_SMOKE_EMAIL ?? 'admin@cosmolab.test';
const password = process.env.VOODBUILDER_SMOKE_PASSWORD ?? 'password';

await page.goto(origin + '/admin/login', { waitUntil: 'domcontentloaded', timeout: 60_000 });
await page.fill('input[type="email"], input[id$="email"]', email);
await page.fill('input[type="password"], input[id$="password"]', password);
await Promise.all([
    page.waitForNavigation({ waitUntil: 'domcontentloaded', timeout: 60_000 }).catch(() => {}),
    page.click('button[type="submit"]'),
]);

// Login redirects into the panel; clear listeners so only editor assets are counted.
requestedScripts.length = 0;
errors.length = 0;

const started = Date.now();

await page.goto(url, { waitUntil: 'domcontentloaded', timeout: 60_000 });

// Boot is done when the splash class is gone from the editor root.
let bootMs = null;

try {
    await page.waitForFunction(
        () => {
            const root = document.querySelector('.voodbuilder-editor-root');

            return root && ! root.classList.contains('voodbuilder-editor-root--booting');
        },
        { timeout: 45_000 },
    );
    bootMs = Date.now() - started;
} catch {
    bootMs = null;
}

// Give late boot tasks a chance to settle before sampling.
await page.waitForTimeout(3_000);

const state = await page.evaluate(async () => {
    const canvasFrame = document.querySelector('iframe');
    const canvasDoc = canvasFrame?.contentDocument ?? null;

    // Responsiveness: how many animation frames land in one second.
    const fps = await new Promise((resolve) => {
        let frames = 0;
        const stop = performance.now() + 1_000;
        const tick = () => {
            frames += 1;
            if (performance.now() < stop) {
                requestAnimationFrame(tick);
            } else {
                resolve(frames);
            }
        };
        requestAnimationFrame(tick);
    });

    return {
        fps,
        blocks: document.querySelectorAll('[data-voodbuilder-block-card], .gjs-block').length,
        layers: document.querySelectorAll('.gjs-layer').length,
        canvasChildren: canvasDoc?.body?.children.length ?? 0,
        canvasReady: Boolean(canvasDoc?.body?.classList.contains('voodbuilder-canvas-ready')),
        hasEditorApi: typeof window.grapesjs !== 'undefined' || Boolean(document.querySelector('.gjs-cv-canvas')),
    };
});

const bootScripts = requestedScripts.filter((name) => /\.js$/.test(name ?? ''));
const loadedTailwindCompiler = bootScripts.some((n) => n?.includes('vendor-tailwind-compiler'));
const loadedCodeMirror = bootScripts.some((n) => n?.includes('code-editor-field-cm'));
const loadedJodit = bootScripts.some((n) => n?.includes('jodit-image-editor'));
const loadedVendorGrapes = bootScripts.some((n) => n?.includes('vendor-grapesjs-'));

console.log(JSON.stringify({
    url,
    bootMs,
    ...state,
    scriptsRequested: bootScripts.length,
    loadedVendorGrapes,
    lazyStayedLazy: {
        tailwindCompiler: ! loadedTailwindCompiler,
        codeMirror: ! loadedCodeMirror,
        jodit: ! loadedJodit,
    },
    errors: errors.slice(0, 12),
}, null, 2));

await page.screenshot({ path: '/tmp/editor-smoke.png', fullPage: false });
await browser.close();

const ok = bootMs !== null && state.blocks > 0 && errors.length === 0;
process.exit(ok ? 0 : 1);

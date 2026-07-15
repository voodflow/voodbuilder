/**
 * Renders the import compatibility report returned by compile-css.
 */

function escapeHtml(value) {
    return String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
}

function formatLabel(template, replacements = {}) {
    let output = String(template ?? '');

    for (const [key, value] of Object.entries(replacements)) {
        output = output.replaceAll(`:${key}`, String(value));
    }

    return output;
}

function statusLabel(labels, status) {
    const map = {
        excellent: labels.componentsCompatibilityStatusExcellent,
        good: labels.componentsCompatibilityStatusGood,
        partial: labels.componentsCompatibilityStatusPartial,
        poor: labels.componentsCompatibilityStatusPoor,
    };

    return map[status] ?? labels.componentsCompatibilityStatusPartial ?? 'Partial compatibility';
}

export function renderCompatibilityReport(mount, report, labels = {}, options = {}) {
    if (! mount) {
        return [];
    }

    if (! report || ! report.totals) {
        mount.hidden = true;
        mount.replaceChildren();

        return [];
    }

    mount.hidden = false;

    const {
        totals,
        status,
        adaptations = [],
        review = [],
        ready_sample: readySample = [],
        context = {},
    } = report;
    const chromeBlock = Boolean(context.chrome_block);
    const themeReadyCount = Number(totals.theme_ready ?? 0);
    const reviewHint = chromeBlock && review.length > 0
        ? (labels.componentsCompatibilityChromeReviewHint
            ?? 'These classes are not in the block JIT CSS but are usually covered by the canvas theme (nav/footer chrome). Click a class to jump to it in the editor.')
        : (labels.componentsCompatibilityReviewHint
            ?? 'These classes were not found in the block JIT CSS and may be covered by the canvas theme. Click a class to jump to it in the editor.');
    const statusText = statusLabel(labels, status);
    const onReviewClassClick = options.onReviewClassClick ?? null;

    const statsParts = [
        formatLabel(labels.componentsCompatibilityClasses ?? ':count classes detected', { count: totals.classes }),
        formatLabel(labels.componentsCompatibilityReady ?? ':count ready', { count: totals.ready }),
        formatLabel(labels.componentsCompatibilityAdapted ?? ':count adapted', { count: totals.adapted }),
    ];

    if (totals.review > 0) {
        statsParts.push(formatLabel(labels.componentsCompatibilityReview ?? ':count to review', { count: totals.review }));
    }

    const stats = statsParts.join(' · ');

    const adaptationsHtml = adaptations.length === 0
        ? `<p class="voodbuilder-gjs-compatibility__empty">${escapeHtml(labels.componentsCompatibilityNoAdaptations ?? 'No automatic adaptations were needed.')}</p>`
        : `<ul class="voodbuilder-gjs-compatibility__list">${adaptations.map((item) => (
            `<li><code>${escapeHtml(item.from)}</code> → <code>${escapeHtml(item.to)}</code></li>`
        )).join('')}</ul>`;

    const reviewHtml = review.length === 0
        ? ''
        : `<ul class="voodbuilder-gjs-compatibility__chips" aria-label="${escapeHtml(labels.componentsCompatibilityReviewTitle ?? 'Classes to review')}">${review.slice(0, 24).map((item) => (
            `<li class="voodbuilder-gjs-compatibility__chip">
                <button type="button" class="voodbuilder-gjs-compatibility__chip-btn" data-voodbuilder-review-class="${escapeHtml(item)}" title="${escapeHtml(labels.componentsCompatibilityReviewJump ?? 'Jump to class in editor')}">
                    <code>${escapeHtml(item)}</code>
                </button>
            </li>`
        )).join('')}${review.length > 24 ? `<li class="voodbuilder-gjs-compatibility__chip voodbuilder-gjs-compatibility__chip--more">+${review.length - 24}</li>` : ''}</ul>`;

    const chromeBannerHtml = chromeBlock
        ? `<section class="voodbuilder-gjs-compatibility__chrome-banner" aria-label="${escapeHtml(labels.componentsCompatibilityChromeBannerTitle ?? 'Chrome block theme coverage')}">
                <p class="voodbuilder-gjs-compatibility__chrome-banner-text">${escapeHtml(labels.componentsCompatibilityChromeBanner ?? 'Nav/footer chrome blocks rely on the global canvas theme. Remaining review items are often theme utilities, not missing block styles.')}</p>
            </section>`
        : '';

    const reviewBannerClass = chromeBlock && review.length > 0
        ? 'voodbuilder-gjs-compatibility__review-banner voodbuilder-gjs-compatibility__review-banner--chrome'
        : 'voodbuilder-gjs-compatibility__review-banner';

    const reviewBannerHtml = review.length === 0
        ? ''
        : `<section class="${reviewBannerClass}" aria-label="${escapeHtml(labels.componentsCompatibilityReviewTitle ?? 'Classes to review')}">
                <div class="voodbuilder-gjs-compatibility__review-banner-head">
                    <h4 class="voodbuilder-gjs-compatibility__review-banner-title">${escapeHtml(labels.componentsCompatibilityReviewTitle ?? 'Classes to review')}</h4>
                    <span class="voodbuilder-gjs-compatibility__count">${review.length}</span>
                </div>
                <p class="voodbuilder-gjs-compatibility__review-hint">${escapeHtml(reviewHint)}</p>
                ${reviewHtml}
            </section>`;

    const sampleHtml = readySample.length > 0
        ? `<p class="voodbuilder-gjs-compatibility__sample">${escapeHtml(labels.componentsCompatibilityReadySample ?? 'Examples:')} ${readySample.map((item) => `<code>${escapeHtml(item)}</code>`).join(' ')}</p>`
        : '';

    const themeReadyHtml = themeReadyCount > 0
        ? `<p class="voodbuilder-gjs-compatibility__theme-note">${escapeHtml(formatLabel(labels.componentsCompatibilityThemeReady ?? ':count covered by canvas theme', { count: themeReadyCount }))}</p>`
        : '';

    mount.innerHTML = `
        <div class="voodbuilder-gjs-compatibility">
            ${chromeBannerHtml}
            ${reviewBannerHtml}
            <div class="voodbuilder-gjs-compatibility__head">
                <div>
                    <h3 class="voodbuilder-gjs-compatibility__title">${escapeHtml(labels.componentsCompatibilityTitle ?? 'Compatibility report')}</h3>
                    <p class="voodbuilder-gjs-compatibility__stats">${escapeHtml(stats)}</p>
                </div>
                <span class="voodbuilder-gjs-compatibility__badge voodbuilder-gjs-compatibility__badge--${escapeHtml(status)}">${escapeHtml(statusText)}</span>
            </div>
            ${themeReadyHtml}
            ${sampleHtml}
            <details class="voodbuilder-gjs-compatibility__section" ${adaptations.length > 0 ? 'open' : ''}>
                <summary>${escapeHtml(labels.componentsCompatibilityAdaptations ?? 'Automatic adaptations')}</summary>
                <div class="voodbuilder-gjs-compatibility__section-body">
                    ${adaptationsHtml}
                </div>
            </details>
        </div>
    `;

    mount.querySelectorAll('[data-voodbuilder-review-class]').forEach((button) => {
        button.addEventListener('click', () => {
            onReviewClassClick?.(button.getAttribute('data-voodbuilder-review-class'));
        });
    });

    return review;
}

export function renderCompatibilityPlaceholder(mount, labels = {}) {
    if (! mount) {
        return;
    }

    mount.hidden = false;
    mount.innerHTML = `
        <div class="voodbuilder-gjs-compatibility voodbuilder-gjs-compatibility--placeholder">
            <h3 class="voodbuilder-gjs-compatibility__title">${escapeHtml(labels.componentsCompatibilityTitle ?? 'Compatibility report')}</h3>
            <p class="voodbuilder-gjs-compatibility__hint">${escapeHtml(labels.componentsCompatibilityEmpty ?? 'Paste HTML to analyze Tailwind class compatibility.')}</p>
        </div>
    `;
}

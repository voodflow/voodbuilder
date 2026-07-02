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

export function renderCompatibilityReport(mount, report, labels = {}) {
    if (! mount) {
        return;
    }

    if (! report || ! report.totals) {
        mount.hidden = true;
        mount.replaceChildren();

        return;
    }

    mount.hidden = false;

    const { totals, status, adaptations = [], review = [], ready_sample: readySample = [] } = report;
    const statusText = statusLabel(labels, status);

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
        : `<ul class="voodbuilder-gjs-compatibility__chips" aria-label="${escapeHtml(labels.componentsCompatibilityReviewTitle ?? 'Classes to review')}">${review.slice(0, 16).map((item) => (
            `<li class="voodbuilder-gjs-compatibility__chip"><code>${escapeHtml(item)}</code></li>`
        )).join('')}${review.length > 16 ? `<li class="voodbuilder-gjs-compatibility__chip voodbuilder-gjs-compatibility__chip--more">+${review.length - 16}</li>` : ''}</ul>`;

    const reviewSectionHtml = review.length === 0
        ? ''
        : `<details class="voodbuilder-gjs-compatibility__section voodbuilder-gjs-compatibility__section--review" open>
                <summary>
                    <span>${escapeHtml(labels.componentsCompatibilityReviewTitle ?? 'Classes to review')}</span>
                    <span class="voodbuilder-gjs-compatibility__count">${review.length}</span>
                </summary>
                <div class="voodbuilder-gjs-compatibility__section-body">
                    <p class="voodbuilder-gjs-compatibility__review-hint">${escapeHtml(labels.componentsCompatibilityReviewHint ?? 'These classes were not found in the compiled CSS and may render without styles.')}</p>
                    ${reviewHtml}
                </div>
            </details>`;

    const sampleHtml = readySample.length > 0
        ? `<p class="voodbuilder-gjs-compatibility__sample">${escapeHtml(labels.componentsCompatibilityReadySample ?? 'Examples:')} ${readySample.map((item) => `<code>${escapeHtml(item)}</code>`).join(' ')}</p>`
        : '';

    mount.innerHTML = `
        <div class="voodbuilder-gjs-compatibility">
            <div class="voodbuilder-gjs-compatibility__head">
                <div>
                    <h3 class="voodbuilder-gjs-compatibility__title">${escapeHtml(labels.componentsCompatibilityTitle ?? 'Compatibility report')}</h3>
                    <p class="voodbuilder-gjs-compatibility__stats">${escapeHtml(stats)}</p>
                </div>
                <span class="voodbuilder-gjs-compatibility__badge voodbuilder-gjs-compatibility__badge--${escapeHtml(status)}">${escapeHtml(statusText)}</span>
            </div>
            ${sampleHtml}
            <details class="voodbuilder-gjs-compatibility__section" ${adaptations.length > 0 ? 'open' : ''}>
                <summary>${escapeHtml(labels.componentsCompatibilityAdaptations ?? 'Automatic adaptations')}</summary>
                <div class="voodbuilder-gjs-compatibility__section-body">
                    ${adaptationsHtml}
                </div>
            </details>
            ${reviewSectionHtml}
        </div>
    `;
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

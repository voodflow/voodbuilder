<script>
    (function () {
        const root = document.documentElement;
        const config = window.__voodbuilderTheme || {
            showToggle: true,
            defaultMode: 'system',
            locked: false,
        };

        function syncThemeToggleUi(isDark) {
            document.querySelectorAll('[data-theme-toggle]').forEach((button) => {
                button.setAttribute('aria-pressed', isDark ? 'true' : 'false');

                const darkLabel = button.dataset.themeLabelDark || 'Dark mode';
                const lightLabel = button.dataset.themeLabelLight || 'Light mode';
                const nextLabel = isDark ? lightLabel : darkLabel;

                button.setAttribute('aria-label', nextLabel);

                const label = button.querySelector('[data-theme-toggle-label]');

                if (label) {
                    label.textContent = nextLabel;
                }

                button.querySelector('[data-theme-icon="moon"]')?.toggleAttribute('hidden', isDark);
                button.querySelector('[data-theme-icon="sun"]')?.toggleAttribute('hidden', ! isDark);
            });
        }

        function syncFilamentThemeStore(isDark) {
            root.style.setProperty('--default-theme-mode', isDark ? 'dark' : 'light');

            try {
                if (window.Alpine && typeof window.Alpine.store === 'function') {
                    window.Alpine.store('theme', isDark ? 'dark' : 'light');
                }
            } catch (error) {
                // Alpine / Filament may not be on this page.
            }
        }

        function applyTheme(isDark) {
            root.classList.toggle('dark', isDark);
            root.style.colorScheme = isDark ? 'dark' : 'light';
            syncFilamentThemeStore(isDark);
            syncThemeToggleUi(isDark);

            window.dispatchEvent(new CustomEvent('voodbuilder:theme-changed', {
                detail: {
                    isDark,
                },
            }));
        }

        applyTheme(root.classList.contains('dark'));

        document.addEventListener('alpine:init', () => {
            // Filament's dark-mode.js also listens on alpine:init; re-assert after it.
            queueMicrotask(() => {
                if (config.locked) {
                    applyTheme(config.defaultMode === 'dark');

                    return;
                }

                applyTheme(resolvePreferredDark());
            });
        });

        function readStoredTheme() {
            try {
                return localStorage.getItem('theme');
            } catch (error) {
                return null;
            }
        }

        function persistTheme(mode) {
            try {
                if (mode === null) {
                    localStorage.removeItem('theme');
                } else {
                    localStorage.setItem('theme', mode);
                }
            } catch (error) {
                // Ignore storage failures and keep the current in-memory theme.
            }
        }

        function resolvePreferredDark(storedMode = readStoredTheme()) {
            if (config.locked) {
                return config.defaultMode === 'dark';
            }

            if (storedMode === 'dark') {
                return true;
            }

            if (storedMode === 'light') {
                return false;
            }

            if (config.defaultMode === 'dark') {
                return true;
            }

            if (config.defaultMode === 'light') {
                return false;
            }

            return window.matchMedia('(prefers-color-scheme: dark)').matches;
        }

        function toggleTheme() {
            if (config.locked) {
                return;
            }

            const nextDark = ! root.classList.contains('dark');
            persistTheme(nextDark ? 'dark' : 'light');
            applyTheme(nextDark);
        }

        document.querySelectorAll('[data-theme-toggle]').forEach((button) => {
            button.addEventListener('click', () => {
                toggleTheme();
            });
        });

        const colorSchemeQuery = window.matchMedia('(prefers-color-scheme: dark)');

        colorSchemeQuery.addEventListener('change', (event) => {
            if (readStoredTheme() !== null || config.locked) {
                return;
            }

            applyTheme(event.matches);
        });

        let scrollLockCount = 0;

        function getScrollbarWidth() {
            return Math.max(0, window.innerWidth - document.documentElement.clientWidth);
        }

        function setScrollLocked(locked) {
            const root = document.documentElement;
            const isLocked = scrollLockCount + (locked ? 1 : -1) > 0;

            if (locked && ! root.classList.contains('vp-scroll-locked')) {
                root.style.setProperty('--vp-scrollbar-width', `${getScrollbarWidth()}px`);
            }

            scrollLockCount += locked ? 1 : -1;
            scrollLockCount = Math.max(0, scrollLockCount);

            root.classList.toggle('vp-scroll-locked', scrollLockCount > 0);

            if (scrollLockCount === 0) {
                root.style.removeProperty('--vp-scrollbar-width');
            }
        }

        const mobileNav = document.querySelector('[data-mobile-nav]');
        const mobileToggle = document.querySelector('[data-mobile-nav-toggle]');

        function setMobileNavOpen(open) {
            if (! mobileNav || ! mobileToggle) {
                return;
            }

            if (open) {
                mobileNav.hidden = false;
                mobileNav.setAttribute('aria-hidden', 'false');
                requestAnimationFrame(() => {
                    mobileNav.classList.add('is-open');
                });
            } else {
                mobileNav.classList.remove('is-open');
                mobileNav.setAttribute('aria-hidden', 'true');
                window.setTimeout(() => {
                    if (! mobileNav.classList.contains('is-open')) {
                        mobileNav.hidden = true;
                    }
                }, 300);
            }

            mobileToggle.setAttribute('aria-expanded', open ? 'true' : 'false');
            document.body.classList.toggle('voodbuilder-mobile-nav-open', open);
            setScrollLocked(open);
        }

        mobileToggle?.addEventListener('click', () => {
            setMobileNavOpen(! mobileNav.classList.contains('is-open'));
        });

        document.addEventListener('click', (event) => {
            const target = event.target instanceof Element
                ? event.target.closest('[data-mobile-nav-close]')
                : null;

            if (target) {
                setMobileNavOpen(false);
            }
        });

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && mobileNav?.classList.contains('is-open')) {
                setMobileNavOpen(false);
            }
        });

        const article = document.querySelector(
            '[data-tutorial-article], [data-doc-article], [data-vdocs-article], [data-voodbuilder-article]',
        );
        const progressTracks = [...document.querySelectorAll('[data-voodbuilder-progress]')];
        const isPageScrollable = () => document.documentElement.scrollHeight > window.innerHeight + 2;

        const getOutlineScrollOffset = () => {
            const parsed = Number.parseFloat(
                getComputedStyle(root).getPropertyValue('--spacing-vp-doc-offset'),
            );

            return Number.isFinite(parsed) && parsed > 0 ? parsed : 96;
        };

        const getOutlineSpyOffset = () => getOutlineScrollOffset();

        const resolveProgressColor = (track) => {
            const raw = String(track.getAttribute('data-vb-progress-color') || 'brand').trim();

            if (raw.startsWith('#') || raw.startsWith('rgb') || raw.startsWith('hsl') || raw.startsWith('var(')) {
                return raw;
            }

            const map = {
                brand: 'var(--color-vp-brand-1, #6366f1)',
                'brand-2': 'var(--color-vp-brand-2, #818cf8)',
                light: '#e2e8f0',
                dark: '#0f172a',
            };

            return map[raw] || map.brand;
        };

        const applyProgressAppearance = (track) => {
            const thickness = Number.parseInt(track.getAttribute('data-vb-progress-thickness') || '3', 10);
            const px = Number.isFinite(thickness) && thickness > 0 ? thickness : 3;

            track.style.setProperty('--vb-progress-color', resolveProgressColor(track));
            track.style.setProperty('--vb-progress-height', `${px}px`);
        };

        progressTracks.forEach((progressTrack) => {
            const bar = progressTrack.querySelector('[data-reading-progress]');

            if (! (bar instanceof HTMLElement)) {
                return;
            }

            // Standalone Utilities block — owned by site-runtime (full Tailwind color map).
            if (progressTrack.classList.contains('vb-reading-progress')) {
                return;
            }

            applyProgressAppearance(progressTrack);

            // site-runtime / vb-runtime may already own this track.
            if (progressTrack.dataset.vbProgressReady === '1') {
                return;
            }

            progressTrack.dataset.vbProgressReady = '1';
            progressTrack.removeAttribute('hidden');
            progressTrack.removeAttribute('data-inactive');

            let progressScrollable = null;

            const syncProgressLayout = (scrollable) => {
                if (progressScrollable === scrollable) {
                    return;
                }

                progressScrollable = scrollable;

                // Nav-embedded tracks collapse when the page is not scrollable.
                // Standalone Utilities (.vb-reading-progress) stay visible.
                if (! progressTrack.classList.contains('vb-reading-progress')) {
                    root.style.setProperty('--spacing-vp-progress', scrollable ? '2px' : '0px');
                    progressTrack.hidden = ! scrollable;
                    progressTrack.toggleAttribute('data-inactive', ! scrollable);
                }

                if (! scrollable) {
                    bar.style.width = '0%';
                }

                window.dispatchEvent(new Event('resize'));
            };

            const updateProgress = () => {
                const scrollable = isPageScrollable();

                syncProgressLayout(scrollable);

                if (! scrollable) {
                    return;
                }

                const scrollTop = window.scrollY || document.documentElement.scrollTop;
                const viewport = window.innerHeight;

                if (article) {
                    const rect = article.getBoundingClientRect();
                    const top = scrollTop + rect.top;
                    const height = article.offsetHeight;
                    const max = Math.max(height - viewport, 1);
                    const progress = Math.min(Math.max((scrollTop - top) / max, 0), 1);

                    bar.style.width = `${progress * 100}%`;
                    progressTrack.classList.toggle('is-active', progress > 0.001);

                    return;
                }

                const max = Math.max(document.documentElement.scrollHeight - viewport, 1);
                const progress = Math.min(Math.max(scrollTop / max, 0), 1);

                bar.style.width = `${progress * 100}%`;
                progressTrack.classList.toggle('is-active', progress > 0.001);
            };

            updateProgress();
            window.addEventListener('scroll', updateProgress, { passive: true });
            window.addEventListener('resize', updateProgress);
            window.addEventListener('load', updateProgress);
        });

        const searchRoot = document.querySelector('[data-voodbuilder-search]');

        if (searchRoot) {
            const searchDialog = searchRoot.querySelector('[data-voodbuilder-search-dialog]');
            const searchOpen = searchRoot.querySelector('[data-voodbuilder-search-open]');
            const searchInput = searchRoot.querySelector('[data-voodbuilder-search-input]');
            const searchForm = searchRoot.querySelector('[data-voodbuilder-search-form]');
            const suggestUrl = searchRoot.getAttribute('data-voodbuilder-search-suggest-url') || '';
            const searchPageUrl = searchRoot.getAttribute('data-voodbuilder-search-url') || '';
            const listEl = searchRoot.querySelector('[data-voodbuilder-search-list]');
            const hintEl = searchRoot.querySelector('[data-voodbuilder-search-hint]');
            const emptyEl = searchRoot.querySelector('[data-voodbuilder-search-empty]');
            const loadingEl = searchRoot.querySelector('[data-voodbuilder-search-loading]');
            let i18n = {};

            try {
                i18n = JSON.parse(searchRoot.getAttribute('data-voodbuilder-search-i18n') || '{}');
            } catch (e) {
                i18n = {};
            }

            const RECENT_KEY = 'voodbuilder.search.recent';
            let activeIndex = -1;
            let currentItems = [];
            let debounceTimer = null;
            let abortController = null;
            let lastQuery = '';

            function escapeHtml(value) {
                return String(value ?? '')
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;')
                    .replace(/"/g, '&quot;');
            }

            function readRecent() {
                try {
                    const raw = localStorage.getItem(RECENT_KEY);
                    const parsed = raw ? JSON.parse(raw) : [];

                    return Array.isArray(parsed) ? parsed.filter((item) => item && item.url && item.title).slice(0, 6) : [];
                } catch (e) {
                    return [];
                }
            }

            function pushRecent(item) {
                if (! item?.url || ! item?.title) {
                    return;
                }

                const next = [
                    {
                        title: item.title,
                        url: item.url,
                        meta: item.meta || item.channel_label || null,
                        channel_label: item.channel_label || null,
                    },
                    ...readRecent().filter((row) => row.url !== item.url),
                ].slice(0, 6);

                try {
                    localStorage.setItem(RECENT_KEY, JSON.stringify(next));
                } catch (e) {
                    // ignore quota / private mode
                }
            }

            function setPanelMode(mode) {
                hintEl?.classList.toggle('hidden', mode !== 'hint');
                loadingEl?.classList.toggle('hidden', mode !== 'loading');
                emptyEl?.classList.toggle('hidden', mode !== 'empty');
                listEl?.classList.toggle('hidden', mode !== 'list');
            }

            function setActive(index) {
                if (! listEl) {
                    return;
                }

                const options = listEl.querySelectorAll('[data-voodbuilder-search-option]');
                activeIndex = index;

                options.forEach((option, i) => {
                    const active = i === index;
                    option.classList.toggle('is-active', active);
                    option.setAttribute('aria-selected', active ? 'true' : 'false');

                    if (active) {
                        option.scrollIntoView({ block: 'nearest' });
                    }
                });
            }

            function renderItems(items, { heading = null, showViewAll = false, total = 0, query = '' } = {}) {
                if (! listEl) {
                    return;
                }

                currentItems = items;
                activeIndex = items.length ? 0 : -1;

                if (! items.length) {
                    setPanelMode('empty');
                    if (emptyEl) {
                        emptyEl.textContent = (i18n.no_results || 'No results').replace(':query', query);
                    }

                    return;
                }

                const parts = [];

                if (heading) {
                    parts.push(`<p class="px-4 pb-1 pt-1 text-[11px] font-semibold uppercase tracking-[0.08em] text-vp-text-3">${escapeHtml(heading)}</p>`);
                }

                items.forEach((item, index) => {
                    const metaBits = [item.channel_label, item.meta]
                        .filter(Boolean)
                        .map((part) => escapeHtml(part));
                    const meta = metaBits.length
                        ? `<p class="mt-0.5 truncate text-[12px] text-vp-text-3">${metaBits.join(' › ')}</p>`
                        : '';
                    const title = item.title_html || escapeHtml(item.title || '');
                    const href = escapeHtml(item.url || '#');

                    parts.push(`
                        <a
                            href="${href}"
                            class="vb-search-option mx-2 flex items-start gap-3 rounded-lg px-3 py-2.5 text-sm text-vp-text-1 transition-colors"
                            role="option"
                            id="voodbuilder-search-option-${index}"
                            data-voodbuilder-search-option
                            data-index="${index}"
                            aria-selected="${index === 0 ? 'true' : 'false'}"
                        >
                            <span class="mt-0.5 shrink-0 font-semibold text-vp-text-3" aria-hidden="true">#</span>
                            <span class="min-w-0 flex-1">
                                <span class="block truncate font-medium text-vp-text-1">${title}</span>
                                ${meta}
                            </span>
                        </a>
                    `);
                });

                if (showViewAll && searchPageUrl && query) {
                    const href = escapeHtml(`${searchPageUrl}${searchPageUrl.includes('?') ? '&' : '?'}q=${encodeURIComponent(query)}`);
                    const label = escapeHtml((i18n.view_all || 'View all').replace(':count', String(total || items.length)));
                    parts.push(`
                        <a
                            href="${href}"
                            class="mt-1 block border-t border-vp-divider px-4 py-2.5 text-[13px] font-medium text-vp-brand-1 hover:bg-vp-gray-soft"
                            data-voodbuilder-search-view-all
                        >${label}</a>
                    `);
                }

                listEl.innerHTML = parts.join('');
                setPanelMode('list');
                setActive(0);

                listEl.querySelectorAll('[data-voodbuilder-search-option]').forEach((option) => {
                    option.addEventListener('mouseenter', () => {
                        setActive(Number(option.getAttribute('data-index') || 0));
                    });
                    option.addEventListener('click', () => {
                        const idx = Number(option.getAttribute('data-index') || 0);
                        pushRecent(currentItems[idx]);
                    });
                });
            }

            function showIdle() {
                const recent = readRecent();

                if (recent.length) {
                    renderItems(recent.map((row) => ({
                        ...row,
                        title_html: row.title,
                    })), { heading: i18n.recent || 'Recent' });
                    return;
                }

                if (hintEl && i18n.hint) {
                    hintEl.textContent = i18n.hint;
                }

                setPanelMode('hint');
                currentItems = [];
                activeIndex = -1;
            }

            async function runSuggest(query) {
                if (! suggestUrl || query.length < 2) {
                    showIdle();
                    return;
                }

                if (abortController) {
                    abortController.abort();
                }

                abortController = new AbortController();
                setPanelMode('loading');
                lastQuery = query;

                try {
                    const url = `${suggestUrl}${suggestUrl.includes('?') ? '&' : '?'}q=${encodeURIComponent(query)}`;
                    const response = await fetch(url, {
                        headers: { Accept: 'application/json' },
                        signal: abortController.signal,
                        credentials: 'same-origin',
                    });

                    if (! response.ok) {
                        throw new Error('suggest failed');
                    }

                    const data = await response.json();

                    if (query !== lastQuery) {
                        return;
                    }

                    renderItems(data.items || [], {
                        heading: i18n.results || 'Results',
                        showViewAll: (data.total || 0) > (data.items || []).length,
                        total: data.total || 0,
                        query,
                    });
                } catch (error) {
                    if (error?.name === 'AbortError') {
                        return;
                    }

                    setPanelMode('empty');
                    if (emptyEl) {
                        emptyEl.textContent = (i18n.no_results || 'No results').replace(':query', query);
                    }
                }
            }

            function scheduleSuggest() {
                const query = (searchInput?.value || '').trim();

                window.clearTimeout(debounceTimer);

                if (query.length < 2) {
                    showIdle();
                    return;
                }

                debounceTimer = window.setTimeout(() => runSuggest(query), 220);
            }

            function goToActiveOrSearch() {
                if (activeIndex >= 0 && currentItems[activeIndex]?.url) {
                    pushRecent(currentItems[activeIndex]);
                    window.location.href = currentItems[activeIndex].url;
                    return;
                }

                searchForm?.requestSubmit();
            }

            function setSearchOpen(open) {
                if (! searchDialog || ! searchOpen) {
                    return;
                }

                searchDialog.hidden = ! open;
                searchDialog.classList.toggle('hidden', ! open);
                searchDialog.classList.toggle('flex', open);
                searchOpen.setAttribute('aria-expanded', open ? 'true' : 'false');
                searchInput?.setAttribute('aria-expanded', open ? 'true' : 'false');

                if (open) {
                    window.setTimeout(() => {
                        searchInput?.focus();
                        scheduleSuggest();
                    }, 0);
                } else if (abortController) {
                    abortController.abort();
                }
            }

            searchOpen?.addEventListener('click', () => {
                setSearchOpen(searchDialog.hidden);
            });

            searchRoot.querySelectorAll('[data-voodbuilder-search-close]').forEach((element) => {
                element.addEventListener('click', () => setSearchOpen(false));
            });

            searchInput?.addEventListener('input', scheduleSuggest);

            searchForm?.addEventListener('submit', (event) => {
                if (activeIndex >= 0 && currentItems[activeIndex]?.url && (searchInput?.value || '').trim().length >= 2) {
                    event.preventDefault();
                    goToActiveOrSearch();
                }
            });

            searchInput?.addEventListener('keydown', (event) => {
                if (! searchDialog || searchDialog.hidden) {
                    return;
                }

                if (event.key === 'ArrowDown') {
                    event.preventDefault();
                    if (! currentItems.length) {
                        return;
                    }
                    setActive(Math.min(activeIndex + 1, currentItems.length - 1));
                    return;
                }

                if (event.key === 'ArrowUp') {
                    event.preventDefault();
                    if (! currentItems.length) {
                        return;
                    }
                    setActive(Math.max(activeIndex - 1, 0));
                    return;
                }

                if (event.key === 'Enter') {
                    if (activeIndex >= 0 && currentItems[activeIndex]?.url) {
                        event.preventDefault();
                        goToActiveOrSearch();
                    }
                }
            });

            document.addEventListener('keydown', (event) => {
                if ((event.metaKey || event.ctrlKey) && event.key.toLowerCase() === 'k') {
                    event.preventDefault();
                    setSearchOpen(true);
                    return;
                }

                if (event.key === 'Escape' && searchDialog && ! searchDialog.hidden) {
                    event.preventDefault();
                    setSearchOpen(false);
                }
            });

            document.addEventListener('keydown', (event) => {
                if (event.key !== '/' || (searchDialog && ! searchDialog.hidden)) {
                    return;
                }

                const target = event.target;

                if (
                    target instanceof HTMLElement
                    && (target.isContentEditable
                        || ['INPUT', 'SELECT', 'TEXTAREA'].includes(target.tagName))
                ) {
                    return;
                }

                event.preventDefault();
                setSearchOpen(true);
            });
        }

        document.querySelectorAll('.vp-aside-anchor.is-active, .vp-aside-anchor.active').forEach((link) => {
            link.classList.remove('is-active', 'active');
            link.removeAttribute('aria-current');
        });

        const outlineLinks = [...document.querySelectorAll('[data-toc-link]')];

        if (outlineLinks.length > 0 && ! window.__vpOutlineScrollSpy) {
            window.__vpOutlineScrollSpy = 'voodbuilder';
            const entriesById = new Map();

            outlineLinks.forEach((link) => {
                const id = link.getAttribute('href')?.slice(1);

                if (! id) {
                    return;
                }

                const heading = document.getElementById(id);

                if (! heading) {
                    return;
                }

                if (! entriesById.has(id)) {
                    entriesById.set(id, { id, heading, links: [] });
                }

                entriesById.get(id).links.push(link);
            });

            const outlineEntries = [...entriesById.values()];

            if (outlineEntries.length > 0) {
                let activeId = '';

                const setActiveOutline = (id) => {
                    if (id === activeId) {
                        return;
                    }

                    activeId = id;

                    outlineLinks.forEach((link) => {
                        link.classList.remove('is-active', 'active');
                        link.removeAttribute('aria-current');
                    });

                    if (id === '') {
                        return;
                    }

                    const entry = entriesById.get(id);

                    if (! entry) {
                        return;
                    }

                    entry.links.forEach((link) => {
                        link.classList.add('is-active', 'active');
                        link.setAttribute('aria-current', 'location');
                    });
                };

                const sortByDocumentPosition = (items) => [...items].sort((a, b) => {
                    if (a.heading === b.heading) {
                        return 0;
                    }

                    const position = a.heading.compareDocumentPosition(b.heading);

                    if (position & Node.DOCUMENT_POSITION_FOLLOWING) {
                        return -1;
                    }

                    if (position & Node.DOCUMENT_POSITION_PRECEDING) {
                        return 1;
                    }

                    return 0;
                });

                const resolveActiveOutlineId = (items, spyOffset) => {
                    const sorted = sortByDocumentPosition(items);

                    if (sorted.length === 0) {
                        return '';
                    }

                    const scrollY = window.scrollY || document.documentElement.scrollTop;
                    const scrollBottom = scrollY + window.innerHeight;
                    const pageEnd = document.documentElement.scrollHeight;

                    if (pageEnd - scrollBottom < 80) {
                        return sorted[sorted.length - 1].id;
                    }

                    let active = sorted[0].id;

                    sorted.forEach(({ heading, id }) => {
                        if (heading.getBoundingClientRect().top <= spyOffset + 1) {
                            active = id;
                        }
                    });

                    return active;
                };

                const scrollToOutlineHeading = (heading, smooth = true) => {
                    const offset = getOutlineScrollOffset();
                    const top = Math.max(0, heading.getBoundingClientRect().top + window.scrollY - offset);

                    window.scrollTo({ top, behavior: smooth ? 'smooth' : 'instant' });
                };

                const updateOutlineFromScroll = () => {
                    setActiveOutline(resolveActiveOutlineId(outlineEntries, getOutlineSpyOffset()));
                };

                let outlineTicking = false;

                const onOutlineScroll = () => {
                    if (outlineTicking) {
                        return;
                    }

                    outlineTicking = true;

                    requestAnimationFrame(() => {
                        outlineTicking = false;
                        updateOutlineFromScroll();
                    });
                };

                const navigateOutlineTo = (id, smooth = true) => {
                    if (! id || ! entriesById.has(id)) {
                        return;
                    }

                    const { heading } = entriesById.get(id);

                    setActiveOutline(id);
                    history.pushState(null, '', `#${id}`);
                    scrollToOutlineHeading(heading, smooth);
                };

                outlineLinks.forEach((link) => {
                    link.addEventListener('click', (event) => {
                        if (event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) {
                            return;
                        }

                        const id = link.getAttribute('href')?.slice(1);

                        if (! id || ! entriesById.has(id)) {
                            return;
                        }

                        event.preventDefault();
                        navigateOutlineTo(id);
                    });
                });

                const hashId = window.location.hash.slice(1);

                if (hashId && entriesById.has(hashId)) {
                    navigateOutlineTo(hashId, false);
                } else {
                    updateOutlineFromScroll();
                }

                window.addEventListener('scroll', onOutlineScroll, { passive: true });
                window.addEventListener('resize', updateOutlineFromScroll);
                window.addEventListener('load', updateOutlineFromScroll);
                window.addEventListener('hashchange', () => {
                    const id = window.location.hash.slice(1);

                    if (id && entriesById.has(id)) {
                        navigateOutlineTo(id, false);
                    }
                });

                const outlineWatchTarget = document.querySelector(
                    '[data-doc-article], [data-vdocs-article], [data-voodbuilder-article], .vp-doc',
                );

                if (outlineWatchTarget && typeof ResizeObserver !== 'undefined') {
                    let outlineResizeTimer = null;

                    new ResizeObserver(() => {
                        clearTimeout(outlineResizeTimer);
                        outlineResizeTimer = setTimeout(updateOutlineFromScroll, 100);
                    }).observe(outlineWatchTarget);
                }
            }
        }

        document.addEventListener('click', (event) => {
            const trigger = event.target instanceof Element
                ? event.target.closest('[data-cookie-preferences]')
                : null;

            if (trigger) {
                event.preventDefault();
                if (typeof window.__vcookiebar?.open === 'function') {
                    window.__vcookiebar.open();
                } else {
                    document.querySelector('.cc-revoke')?.click();
                }
            }
        });

        document.querySelectorAll('[data-code-copy]').forEach((button) => {
            button.addEventListener('click', async () => {
                const block = button.closest('[data-code-block]');

                if (! block) {
                    return;
                }

                const code = block.querySelector('code')?.textContent?.trim() ?? '';

                if (code === '') {
                    return;
                }

                try {
                    await navigator.clipboard.writeText(code);
                    const original = button.textContent;
                    button.textContent = 'Copied';
                    window.setTimeout(() => {
                        button.textContent = original;
                    }, 1600);
                } catch {
                    button.textContent = 'Failed';
                    window.setTimeout(() => {
                        button.textContent = 'Copy';
                    }, 1600);
                }
            });
        });

        function findTabsRoot(tablist) {
            let node = tablist.parentElement;

            while (node) {
                if (node.querySelector('[role="tabpanel"]')) {
                    return node;
                }

                node = node.parentElement;
            }

            return tablist.parentElement;
        }

        function initEditorTabs(root) {
            if (! root || root.dataset.voodbuilderTabsReady === '1') {
                return;
            }

            root.dataset.voodbuilderTabsReady = '1';

            const classTabActive = root.dataset.vbTabActiveClass
                || root.closest('[data-vb-tab-active-class]')?.dataset.vbTabActiveClass
                || 'tab-active';
            const selectorTab = 'aria-controls';
            const roleTab = '[role="tab"]';
            const roleTabContent = '[role="tabpanel"]';
            const body = document.body;
            const matches = body.matchesSelector
                || body.webkitMatchesSelector
                || body.mozMatchesSelector
                || body.msMatchesSelector;

            const each = (items, callback) => {
                const list = items || [];

                for (let index = 0; index < list.length; index += 1) {
                    callback(list[index], index);
                }
            };

            const hideContents = () => {
                each(root.querySelectorAll(roleTabContent), (panel) => {
                    panel.hidden = true;
                });
            };

            const getTabId = (item) => item.getAttribute(selectorTab);
            const query = (element, selector) => element.querySelector(selector);
            const getAllTabs = () => root.querySelectorAll(roleTab);

            const activeTab = (tabEl) => {
                each(getAllTabs(), (item) => {
                    item.className = item.className.replace(classTabActive, '').trim();
                    item.setAttribute('aria-selected', 'false');
                    item.tabIndex = -1;
                });
                hideContents();
                tabEl.className += ` ${classTabActive}`;
                tabEl.setAttribute('aria-selected', 'true');
                tabEl.tabIndex = 0;
                const tabContentId = getTabId(tabEl);
                const tabContent = tabContentId && query(root, `#${tabContentId}`);

                if (tabContent) {
                    tabContent.hidden = false;
                }
            };

            const getTabByHash = () => {
                const hashId = (window.location.hash || '').replace('#', '');
                const selector = `${roleTab}[${selectorTab}="${hashId}"]`;

                return hashId ? query(root, selector) : null;
            };

            const getSelectedTab = (target) => {
                let found = null;

                each(getAllTabs(), (item) => {
                    if (found) {
                        return;
                    }

                    if (item.contains(target)) {
                        found = item;
                    }
                });

                return found;
            };

            let tabToActive = query(root, `.${classTabActive}${roleTab}`);
            tabToActive = tabToActive || getTabByHash() || query(root, roleTab);

            if (tabToActive) {
                activeTab(tabToActive);
            }

            root.addEventListener('click', (event) => {
                let { target } = event;
                let found = matches.call(target, roleTab);

                if (! found) {
                    target = getSelectedTab(target);
                    found = target ? 1 : 0;
                }

                if (found && ! event.__voodbuilderTabTrigger && target.className.indexOf(classTabActive) < 0) {
                    event.preventDefault();
                    event.__voodbuilderTabTrigger = 1;
                    activeTab(target);
                    const id = getTabId(target);

                    try {
                        if (window.history) {
                            window.history.pushState(null, '', `#${id}`);
                        }
                    } catch {
                        // Ignore history API failures.
                    }
                }
            });
        }

        document.querySelectorAll('[role="tablist"]').forEach((tablist) => {
            initEditorTabs(findTabsRoot(tablist));
        });
    })();
</script>

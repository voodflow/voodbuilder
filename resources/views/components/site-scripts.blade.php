<script>
    (function () {
        const root = document.documentElement;
        const config = window.__vpressTheme || {
            showToggle: true,
            defaultMode: 'system',
            locked: false,
        };

        function applyTheme(isDark) {
            root.classList.toggle('dark', isDark);
            root.style.colorScheme = isDark ? 'dark' : 'light';

            document.querySelectorAll('[data-theme-toggle]').forEach((button) => {
                button.setAttribute('aria-pressed', isDark ? 'true' : 'false');
            });
        }

        applyTheme(root.classList.contains('dark'));

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
            document.body.classList.toggle('vpress-mobile-nav-open', open);
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
            '[data-tutorial-article], [data-doc-article], [data-vdocs-article], [data-vpress-article]',
        );
        const bar = document.querySelector('[data-reading-progress]');
        const progressTrack = bar?.closest('[data-vpress-progress]');
        const isPageScrollable = () => document.documentElement.scrollHeight > window.innerHeight + 2;

        const getOutlineScrollOffset = () => {
            const parsed = Number.parseFloat(
                getComputedStyle(root).getPropertyValue('--spacing-vp-doc-offset'),
            );

            return Number.isFinite(parsed) && parsed > 0 ? parsed : 96;
        };

        const getOutlineSpyOffset = () => getOutlineScrollOffset();

        let progressScrollable = null;

        const syncProgressLayout = (scrollable) => {
            if (! progressTrack) {
                return;
            }

            if (progressScrollable === scrollable) {
                return;
            }

            progressScrollable = scrollable;
            root.style.setProperty('--spacing-vp-progress', scrollable ? '2px' : '0px');
            progressTrack.hidden = ! scrollable;
            progressTrack.toggleAttribute('data-inactive', ! scrollable);

            if (! scrollable) {
                bar.style.width = '0%';
            }

            window.dispatchEvent(new Event('resize'));
        };

        if (article && bar && progressTrack) {
            const updateProgress = () => {
                const scrollable = isPageScrollable();

                syncProgressLayout(scrollable);

                if (! scrollable) {
                    return;
                }

                const rect = article.getBoundingClientRect();
                const scrollTop = window.scrollY || document.documentElement.scrollTop;
                const top = scrollTop + rect.top;
                const height = article.offsetHeight;
                const viewport = window.innerHeight;
                const max = Math.max(height - viewport, 1);
                const progress = Math.min(Math.max((scrollTop - top) / max, 0), 1);

                bar.style.width = `${progress * 100}%`;
                progressTrack.classList.toggle('is-active', progress > 0.001);
            };

            updateProgress();
            window.addEventListener('scroll', updateProgress, { passive: true });
            window.addEventListener('resize', updateProgress);
            window.addEventListener('load', updateProgress);
        }

        const searchRoot = document.querySelector('[data-vpress-search]');

        if (searchRoot) {
            const searchDialog = searchRoot.querySelector('[data-vpress-search-dialog]');
            const searchOpen = searchRoot.querySelector('[data-vpress-search-open]');
            const searchInput = searchRoot.querySelector('[data-vpress-search-input]');

            function setSearchOpen(open) {
                if (! searchDialog || ! searchOpen) {
                    return;
                }

                searchDialog.hidden = ! open;
                searchDialog.classList.toggle('hidden', ! open);
                searchDialog.classList.toggle('flex', open);
                searchOpen.setAttribute('aria-expanded', open ? 'true' : 'false');

                if (open) {
                    window.setTimeout(() => searchInput?.focus(), 0);
                }
            }

            searchOpen?.addEventListener('click', () => {
                setSearchOpen(searchDialog.hidden);
            });

            searchRoot.querySelectorAll('[data-vpress-search-close]').forEach((element) => {
                element.addEventListener('click', () => setSearchOpen(false));
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
            window.__vpOutlineScrollSpy = 'vpress';
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
                    '[data-doc-article], [data-vdocs-article], [data-vpress-article], .vp-doc',
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
    })();
</script>

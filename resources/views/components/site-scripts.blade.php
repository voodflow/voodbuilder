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

        if (! config.locked && config.showToggle) {
            document.querySelectorAll('[data-theme-toggle]').forEach((button) => {
                if (button.dataset.themeBound === 'true') {
                    return;
                }

                button.dataset.themeBound = 'true';

                button.addEventListener('click', () => {
                    const nextIsDark = ! root.classList.contains('dark');

                    try {
                        localStorage.setItem('theme', nextIsDark ? 'dark' : 'light');
                    } catch (error) {
                        // Ignore storage errors (private mode, etc.).
                    }

                    applyTheme(nextIsDark);
                });
            });
        }

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

        const article = document.querySelector('[data-tutorial-article], [data-doc-article], [data-vdocs-article]');
        const bar = document.querySelector('[data-reading-progress]');
        const progressTrack = bar?.closest('[data-vpress-progress]');

        if (article && bar) {
            const updateProgress = () => {
                const rect = article.getBoundingClientRect();
                const scrollTop = window.scrollY || document.documentElement.scrollTop;
                const top = scrollTop + rect.top;
                const height = article.offsetHeight;
                const viewport = window.innerHeight;
                const max = Math.max(height - viewport, 1);
                const progress = Math.min(Math.max((scrollTop - top) / max, 0), 1);

                bar.style.width = `${progress * 100}%`;
                progressTrack?.classList.toggle('is-active', progress > 0.001);
            };

            updateProgress();
            window.addEventListener('scroll', updateProgress, { passive: true });
            window.addEventListener('resize', updateProgress);
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

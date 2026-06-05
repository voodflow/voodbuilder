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

        const mobileNav = document.querySelector('[data-mobile-nav]');
        const mobileToggle = document.querySelector('[data-mobile-nav-toggle]');

        function setMobileNavOpen(open) {
            if (! mobileNav || ! mobileToggle) {
                return;
            }

            mobileNav.classList.toggle('is-open', open);
            mobileNav.hidden = ! open;
            mobileNav.setAttribute('aria-hidden', open ? 'false' : 'true');
            mobileToggle.setAttribute('aria-expanded', open ? 'true' : 'false');
            document.body.classList.toggle('VPNavMobileOpen', open);

            const labelOpen = mobileToggle.dataset.labelOpen || 'Open menu';
            const labelClose = mobileToggle.dataset.labelClose || 'Close menu';
            mobileToggle.setAttribute('aria-label', open ? labelClose : labelOpen);
        }

        mobileToggle?.addEventListener('click', () => {
            setMobileNavOpen(! mobileNav.classList.contains('is-open'));
        });

        document.querySelectorAll('[data-mobile-nav-close]').forEach((element) => {
            element.addEventListener('click', () => setMobileNavOpen(false));
        });

        const article = document.querySelector('[data-tutorial-article], [data-doc-article]');
        const bar = document.querySelector('[data-reading-progress]');
        const progressTrack = bar?.closest('.VPProgress');

        if (! article || ! bar) {
            return;
        }

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
    })();
</script>

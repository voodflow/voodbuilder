@php
    use Voodflow\Voodbuilder\Support\VoodbuilderTheme;

    $config = VoodbuilderTheme::clientConfig();
    $initialDark = VoodbuilderTheme::serverInitialDark();
@endphp
<meta name="color-scheme" content="{{ $initialDark ? 'dark' : 'light' }}">
<script>
    (function () {
        const config = @json($config);
        window.__voodbuilderTheme = config;
        let stored = null;

        try {
            stored = localStorage.getItem('theme');
        } catch (error) {
            stored = null;
        }

        const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
        let isDark;

        if (config.locked) {
            isDark = config.defaultMode === 'dark';
        } else if (stored === 'dark') {
            isDark = true;
        } else if (stored === 'light') {
            isDark = false;
        } else if (config.defaultMode === 'dark') {
            isDark = true;
        } else if (config.defaultMode === 'light') {
            isDark = false;
        } else {
            isDark = prefersDark;
        }

        document.documentElement.classList.toggle('dark', isDark);
        document.documentElement.style.colorScheme = isDark ? 'dark' : 'light';
        // Filament comments share localStorage `theme` and --default-theme-mode;
        // keep them aligned so alpine:init does not flash the public site to light.
        document.documentElement.style.setProperty(
            '--default-theme-mode',
            isDark ? 'dark' : 'light',
        );

        if (config.locked) {
            try {
                localStorage.setItem('theme', isDark ? 'dark' : 'light');
            } catch (error) {
                // Ignore storage failures.
            }
        }

        if (/Mac|iPhone|iPod|iPad/i.test(navigator.platform || navigator.userAgent)) {
            document.documentElement.classList.add('mac');
        } else {
            document.documentElement.classList.add('windows');
        }

        if (config.locked) {
            document.documentElement.dataset.themeLocked = 'true';
        } else {
            delete document.documentElement.dataset.themeLocked;
        }
    })();
</script>

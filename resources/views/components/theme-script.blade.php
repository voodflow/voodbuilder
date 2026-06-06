@php
    use Voodflow\Vpress\Support\VpressTheme;

    $config = VpressTheme::clientConfig();
    $initialDark = VpressTheme::serverInitialDark();
@endphp
<meta name="color-scheme" content="{{ $initialDark ? 'dark' : 'light' }}">
<script>
    (function () {
        const config = @json($config);
        window.__vpressTheme = config;
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

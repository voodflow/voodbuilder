@php
    use Voodflow\Voodbuilder\Models\VoodbuilderSettings;
    use Voodflow\Vcookiebar\Support\ConsentPayload;
    use Voodflow\Vcookiebar\Vcookiebar;

    $pixelId = VoodbuilderSettings::get('facebook_pixel_id');
    $gtmId = VoodbuilderSettings::get('google_tag_manager_id');
    $gaId = VoodbuilderSettings::get('google_analytics_id');
    $headCode = VoodbuilderSettings::get('monitoring_head_code');
    $bodyCode = VoodbuilderSettings::get('monitoring_body_code');

    $hasTracking = filled($pixelId) || filled($gtmId) || filled($gaId) || filled($headCode) || filled($bodyCode);

    $consentPreferences = null;
    $consentRequired = false;

    if (class_exists(Vcookiebar::class) && Vcookiebar::isEnabled()) {
        $consentRequired = true;
        $cookieName = (string) config('vcookiebar.consent_cookie', 'vcookiebar_consent');
        $consentPreferences = ConsentPayload::decode(request()->cookie($cookieName));
    }
@endphp

@if($hasTracking)
    <script>
        (function () {
            const config = {
                pixelId: @json($pixelId),
                gtmId: @json($gtmId),
                gaId: @json($gaId),
                headCode: @json($headCode),
                bodyCode: @json($bodyCode),
            };

            const consentRequired = @json($consentRequired);
            window.__vcookiebar = window.__vcookiebar || {};
            window.__vcookiebar.preferences = @json($consentPreferences);

            let loaded = false;

            function hasAnalyticsConsent() {
                if (! consentRequired) {
                    return true;
                }

                const preferences = window.__vcookiebar?.preferences;

                return Boolean(preferences && preferences.analytics === true);
            }

            function injectHtml(html, target) {
                if (!html) {
                    return;
                }

                const container = document.createElement('div');
                container.innerHTML = html;

                Array.from(container.childNodes).forEach(function (node) {
                    if (node.nodeType !== 1) {
                        target.appendChild(node.cloneNode(true));
                        return;
                    }

                    if (node.tagName === 'SCRIPT') {
                        const script = document.createElement('script');
                        Array.from(node.attributes).forEach(function (attr) {
                            script.setAttribute(attr.name, attr.value);
                        });
                        if (node.src) {
                            script.src = node.src;
                        } else {
                            script.text = node.textContent || '';
                        }
                        target.appendChild(script);
                        return;
                    }

                    target.appendChild(node);
                });
            }

            function loadTracking() {
                if (loaded || ! hasAnalyticsConsent()) {
                    return;
                }

                loaded = true;

                if (config.gtmId) {
                    window.dataLayer = window.dataLayer || [];
                    window.dataLayer.push({'gtm.start': new Date().getTime(), event: 'gtm.js'});

                    const gtmScript = document.createElement('script');
                    gtmScript.async = true;
                    gtmScript.src = 'https://www.googletagmanager.com/gtm.js?id=' + config.gtmId;
                    document.head.appendChild(gtmScript);
                }

                if (config.gaId && !config.gtmId) {
                    const gaScript = document.createElement('script');
                    gaScript.async = true;
                    gaScript.src = 'https://www.googletagmanager.com/gtag/js?id=' + config.gaId;
                    document.head.appendChild(gaScript);

                    window.dataLayer = window.dataLayer || [];
                    window.gtag = function () { window.dataLayer.push(arguments); };
                    window.gtag('js', new Date());
                    window.gtag('config', config.gaId);
                }

                if (config.pixelId) {
                    !function (f, b, e, v, n, t, s) {
                        if (f.fbq) return;
                        n = f.fbq = function () {
                            n.callMethod ? n.callMethod.apply(n, arguments) : n.queue.push(arguments);
                        };
                        if (!f._fbq) f._fbq = n;
                        n.push = n;
                        n.loaded = true;
                        n.version = '2.0';
                        n.queue = [];
                        t = b.createElement(e);
                        t.async = true;
                        t.src = v;
                        s = b.getElementsByTagName(e)[0];
                        s.parentNode.insertBefore(t, s);
                    }(window, document, 'script', 'https://connect.facebook.net/en_US/fbevents.js');

                    window.fbq('init', config.pixelId);
                    window.fbq('track', 'PageView');
                }

                injectHtml(config.headCode, document.head);
                injectHtml(config.bodyCode, document.body);
            }

            loadTracking();

            window.addEventListener('vcookiebar:consent', function (event) {
                const detail = event?.detail?.preferences;

                if (detail && typeof detail === 'object') {
                    window.__vcookiebar.preferences = detail;
                }

                loadTracking();
            });

            const consentWatcher = window.setInterval(function () {
                loadTracking();

                if (loaded) {
                    window.clearInterval(consentWatcher);
                }
            }, 1000);
        })();
    </script>
@endif

@php
    use Voodflow\Vpress\Models\VpressSettings;

    $pixelId = VpressSettings::get('facebook_pixel_id');
    $gtmId = VpressSettings::get('google_tag_manager_id');
    $gaId = VpressSettings::get('google_analytics_id');
    $headCode = VpressSettings::get('monitoring_head_code');
    $bodyCode = VpressSettings::get('monitoring_body_code');

    $hasTracking = filled($pixelId) || filled($gtmId) || filled($gaId) || filled($headCode) || filled($bodyCode);
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

            let loaded = false;

            function hasConsent() {
                return document.cookie.split(';').some(function (cookie) {
                    return cookie.trim().startsWith('cookieconsent_status=allow');
                });
            }

            function injectHtml(html, target) {
                if (!html) {
                    return;
                }

                const container = document.createElement('div');
                container.innerHTML = html;
                Array.from(container.children).forEach(function (node) {
                    target.appendChild(node);
                });
            }

            function loadTracking() {
                if (loaded || !hasConsent()) {
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

            const consentWatcher = window.setInterval(function () {
                loadTracking();

                if (loaded) {
                    window.clearInterval(consentWatcher);
                }
            }, 1000);
        })();
    </script>
@endif

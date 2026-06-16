@php($settings = cookie_consent_settings())
<link rel="stylesheet" type="text/css" href="{{ $settings->css_url }}"/>
<style>
    .cc-window {
        background-color: {{ $settings->popup_background }} !important;
        color: {{ $settings->popup_text }} !important;
    }

    .cc-window .cc-message,
    .cc-window .cc-header {
        color: {{ $settings->popup_text }} !important;
    }

    .cc-window .cc-link,
    .cc-window .cc-link:active,
    .cc-window .cc-link:visited {
        color: {{ $settings->popup_link }} !important;
    }

    .cc-window .cc-btn {
        background: {{ $settings->button_background }} !important;
        border-color: {{ $settings->button_border }} !important;
        color: {{ $settings->button_text }} !important;
    }

    .cc-window .cc-btn.cc-allow,
    .cc-window .cc-btn.cc-dismiss {
        background: {{ $settings->highlight_background }} !important;
        border-color: {{ $settings->highlight_border }} !important;
        color: {{ $settings->highlight_text }} !important;
    }

    @media (max-width: 59.999rem) {
        .cc-revoke {
            display: none !important;
        }

        .cc-window.cc-floating {
            left: 0.75rem !important;
            right: 0.75rem !important;
            bottom: 0.75rem !important;
            width: auto !important;
            max-width: none !important;
            font-size: 0.8125rem !important;
        }
    }

    body.vpress-mobile-nav-open .cc-window,
    body.vpress-mobile-nav-open .cc-revoke {
        visibility: hidden !important;
        pointer-events: none !important;
        opacity: 0 !important;
    }
</style>

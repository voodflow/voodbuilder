@php($settings = cookie_consent_settings())
<script src="{{ $settings->js_url }}" data-cfasync="false"></script>
<script>
    window.cookieconsent.initialise({
        type: 'opt-in',
        palette: {
            popup: {
                background: @json($settings->popup_background),
                link: @json($settings->popup_link),
                text: @json($settings->popup_text),
            },
            button: {
                background: @json($settings->button_background),
                border: @json($settings->button_border),
                text: @json($settings->button_text),
            },
            highlight: {
                background: @json($settings->highlight_background),
                border: @json($settings->highlight_border),
                text: @json($settings->highlight_text),
            },
        },
        position: @json($settings->position),
        theme: @json($settings->theme),
        content: {
            header: @json($settings->content_header),
            message: @json($settings->content_message),
            dismiss: @json($settings->content_dismiss),
            allow: @json($settings->content_allow),
            deny: @json($settings->content_deny),
            link: @json($settings->content_link),
            href: @json($settings->content_href),
            close: @json($settings->content_close),
            target: @json($settings->content_target),
            policy: @json($settings->content_policy),
        },
    });
</script>

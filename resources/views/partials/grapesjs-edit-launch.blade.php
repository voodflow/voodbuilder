@php
    $editUrl = request()->fullUrlWithQuery(['edit' => 1]);
@endphp

<a
    href="{{ $editUrl }}"
    class="vpress-grapesjs-edit-launch"
    data-vpress-grapesjs-edit-launch
    aria-label="{{ __('vpress::pro.actions.open_visual_editor') }}"
>
    {{ __('vpress::pro.actions.open_visual_editor') }}
</a>

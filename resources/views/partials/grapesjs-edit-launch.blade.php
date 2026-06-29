@php
    $editUrl = request()->fullUrlWithQuery(['edit' => 1]);
@endphp

<a
    href="{{ $editUrl }}"
    class="voodbuilder-grapesjs-edit-launch"
    data-voodbuilder-grapesjs-edit-launch
    aria-label="{{ __('voodbuilder::pro.actions.open_visual_editor') }}"
>
    {{ __('voodbuilder::pro.actions.open_visual_editor') }}
</a>

@extends('voodbuilder::layouts.admin-preview', [
    'badge' => __('voodbuilder::admin.menu_preview.badge', ['menu' => $menu->name]),
])

@section('preview')
    @include('voodbuilder::admin.partials.navigation-menu-preview-content')
@endsection

@php
    use Voodflow\Voodbuilder\Support\ArticleChannel;

    $prefix = ArticleChannel::shellClassPrefix();
@endphp

@extends(config('voodbuilder.layouts.app', 'voodbuilder::layouts.app'))

@section('body_class')
    {{ ArticleChannel::bodyClass() }}
@endsection

@section('content')
    <div class="{{ $prefix }}-shell" data-voodbuilder-article>
        <div class="{{ $prefix }}-layout">
            @include(ArticleChannel::inkPartial('sidebar-left'), [
                'posts' => $sidebarPosts ?? collect(),
                'currentPost' => $currentPost ?? null,
            ])

            <div class="{{ $prefix }}-main">
                @yield('main')
            </div>

            @include(ArticleChannel::inkPartial('sidebar-right'), [
                'posts' => $sidebarPosts ?? collect(),
                'currentPost' => $currentPost ?? null,
            ])
        </div>
    </div>
@endsection

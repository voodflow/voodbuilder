@extends($page->layoutView())

@section($page->contentSection())
    <div class="VPRichPage">
        {!! $page->renderedContent() !!}
    </div>
@endsection

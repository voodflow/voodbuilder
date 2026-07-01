<header class="text-gray-600 body-font vp-landing-navbar-layout vp-landing-navbar-layout--a">
    <div class="container mx-auto flex flex-wrap p-5 flex-col md:flex-row items-center">
        @include('voodbuilder::blocks.landing.partials.layout-brand', ['brandClass' => 'mb-4 md:mb-0'])
        @include('voodbuilder::blocks.landing.partials.layout-nav-links', [
            'navbar' => $navbar,
            'navClass' => 'md:ml-auto flex flex-wrap items-center text-base justify-center',
        ])
    </div>
</header>

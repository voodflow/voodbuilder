@php
    use Voodflow\Voodbuilder\Support\SitePageAccess;

    $gate = $pageGate ?? null;
    $isPasswordOnly = $gate === SitePageAccess::REASON_PASSWORD;
    $isRegistered = $gate === SitePageAccess::REASON_REGISTERED;
    $isSubscriber = $gate === SitePageAccess::REASON_SUBSCRIBER;
    $offerPassword = (bool) ($pageGateOfferPassword ?? false);
    $requiresEmail = (bool) ($pageGateRequiresEmail ?? false);
    $loginUrl = $pageGateLoginUrl ?? SitePageAccess::loginUrl();
    $registerUrl = $pageGateRegisterUrl ?? SitePageAccess::registerUrl();
    $subscribeUrl = $pageGateSubscribeUrl ?? SitePageAccess::subscribeUrl();
    $previewText = filled($page->excerpt) ? $page->excerpt : null;
@endphp

{{-- Paywall / password gate — fits between nav and footer, no page scroll --}}
<div class="voodbuilder-page-gate mx-auto flex h-full w-full max-w-[80rem] flex-1 flex-col px-4 sm:px-6">
    <div class="relative flex min-h-0 flex-1 items-center justify-center py-6 md:py-8">
        <div
            class="absolute inset-0 overflow-hidden rounded-lg border border-vp-divider bg-vp-bg-alt p-4"
            aria-hidden="true"
        >
            @if ($previewText)
                <p class="relative z-[1] mb-3 line-clamp-2 text-base leading-relaxed text-vp-text-2">
                    {{ $previewText }}
                </p>
            @endif

            <div class="relative z-[1] grid gap-2 pt-4">
                <span class="block h-2.5 rounded-full bg-vp-gray-soft"></span>
                <span class="block h-2.5 rounded-full bg-vp-gray-soft"></span>
                <span class="block h-2.5 w-[62%] rounded-full bg-vp-gray-soft"></span>
                <span class="mt-3 block h-2.5 rounded-full bg-vp-gray-soft"></span>
                <span class="block h-2.5 w-[75%] rounded-full bg-vp-gray-soft"></span>
            </div>

            <div class="pointer-events-none absolute inset-0 bg-gradient-to-b from-transparent via-vp-bg/40 to-vp-bg"></div>
        </div>

        <div
            class="relative z-[2] max-h-full w-[min(100%,34rem)] overflow-y-auto rounded-xl border border-vp-divider bg-vp-bg px-5 py-5 text-center shadow-[0_12px_32px_rgb(0_0_0_/_0.08),0_2px_8px_rgb(0_0_0_/_0.04)] dark:shadow-[0_16px_40px_rgb(0_0_0_/_0.35),0_2px_8px_rgb(0_0_0_/_0.2)] sm:px-6 sm:py-6"
            role="region"
            aria-labelledby="voodbuilder-page-gate-title"
        >
            <div
                class="mx-auto mb-3 inline-flex size-[3.25rem] items-center justify-center rounded-full bg-vp-brand-1/15 text-vp-brand-1"
                aria-hidden="true"
            >
                <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                    <rect x="5" y="11" width="14" height="10" rx="2"/>
                    <path d="M8 11V8a4 4 0 0 1 8 0v3"/>
                </svg>
            </div>

            <p class="mb-2 text-[0.6875rem] font-semibold tracking-[0.1em] text-vp-brand-1 uppercase">
                @if ($isPasswordOnly)
                    {{ __('voodbuilder::gate.password.eyebrow') }}
                @elseif ($isRegistered)
                    {{ __('voodbuilder::gate.registered_eyebrow') }}
                @else
                    {{ __('voodbuilder::gate.subscriber_eyebrow') }}
                @endif
            </p>

            <h2 id="voodbuilder-page-gate-title" class="mb-2.5 text-[1.375rem] leading-tight font-bold text-vp-text-1">
                @if ($isPasswordOnly)
                    {{ $page->title }}
                @elseif ($isSubscriber && auth()->check())
                    {{ __('voodbuilder::gate.subscriber_title_authenticated') }}
                @elseif ($isRegistered)
                    {{ __('voodbuilder::gate.registered_title') }}
                @else
                    {{ __('voodbuilder::gate.subscriber_title') }}
                @endif
            </h2>

            <p class="mb-5 text-[0.9375rem] leading-relaxed text-vp-text-2">
                @if ($isPasswordOnly)
                    {{ __('voodbuilder::gate.password.description') }}
                @elseif ($isSubscriber && auth()->check())
                    {{ __('voodbuilder::gate.subscriber_description_authenticated') }}
                @elseif ($isRegistered)
                    {{ __('voodbuilder::gate.registered_description') }}
                @else
                    {{ __('voodbuilder::gate.subscriber_description') }}
                @endif
            </p>

            @unless ($isPasswordOnly)
                <div class="flex flex-wrap items-center justify-center gap-2.5">
                    @if ($isRegistered || ! auth()->check())
                        <a
                            href="{{ $loginUrl }}"
                            class="inline-flex h-10 items-center rounded-full bg-vp-brand-3 px-5 text-sm font-medium text-white transition-colors hover:bg-vp-brand-2"
                        >
                            {{ __('voodbuilder::gate.login') }}
                        </a>
                    @endif

                    @if ($registerUrl && $isRegistered)
                        <a
                            href="{{ $registerUrl }}"
                            class="inline-flex h-10 items-center rounded-full bg-vp-gray-soft px-5 text-sm font-medium text-vp-text-1 transition-colors hover:bg-vp-divider"
                        >
                            {{ __('voodbuilder::gate.register') }}
                        </a>
                    @endif

                    @if ($subscribeUrl && $isSubscriber)
                        <a
                            href="{{ $subscribeUrl }}"
                            @class([
                                'inline-flex h-10 items-center rounded-full px-5 text-sm font-medium transition-colors',
                                'bg-vp-brand-3 text-white hover:bg-vp-brand-2' => auth()->check(),
                                'bg-vp-gray-soft text-vp-text-1 hover:bg-vp-divider' => ! auth()->check(),
                            ])
                        >
                            {{ __('voodbuilder::gate.subscribe') }}
                        </a>
                    @endif
                </div>

                @if ($isSubscriber && ! $subscribeUrl)
                    <p class="mt-4 text-[0.8125rem] leading-snug text-vp-text-3">
                        {{ auth()->check()
                            ? __('voodbuilder::gate.subscriber_hint_authenticated')
                            : __('voodbuilder::gate.subscriber_hint') }}
                    </p>
                @endif
            @endunless

            @if ($offerPassword)
                @unless ($isPasswordOnly)
                    <div class="my-5 flex items-center gap-3" aria-hidden="true">
                        <span class="h-px flex-1 bg-vp-divider"></span>
                        <span class="text-[0.6875rem] font-semibold tracking-[0.08em] text-vp-text-3 uppercase">
                            {{ __('voodbuilder::gate.password.or_access_code') }}
                        </span>
                        <span class="h-px flex-1 bg-vp-divider"></span>
                    </div>
                @endunless

                <form
                    method="post"
                    action="{{ route('voodbuilder.pages.unlock', ['slug' => $page->slug]) }}"
                    class="mx-auto grid w-full max-w-sm gap-3 text-left"
                >
                    @csrf

                    @if ($requiresEmail)
                        <div>
                            <label class="mb-1 block text-sm font-medium text-vp-text-1" for="voodbuilder-page-gate-email">
                                {{ __('voodbuilder::gate.password.email') }}
                            </label>
                            <input
                                id="voodbuilder-page-gate-email"
                                type="email"
                                name="email"
                                value="{{ old('email') }}"
                                autocomplete="username"
                                placeholder="{{ __('voodbuilder::gate.password.email_placeholder') }}"
                                class="w-full rounded-lg border border-vp-divider bg-vp-bg px-3 py-2 text-vp-text-1 outline-none focus:border-vp-brand-1"
                                required
                            >
                            @error('email')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>
                    @endif

                    <div>
                        <label class="mb-1 block text-sm font-medium text-vp-text-1" for="voodbuilder-page-gate-password">
                            {{ __('voodbuilder::gate.password.password') }}
                        </label>
                        <input
                            id="voodbuilder-page-gate-password"
                            type="password"
                            name="password"
                            autocomplete="current-password"
                            placeholder="{{ __('voodbuilder::gate.password.password_placeholder') }}"
                            class="w-full rounded-lg border border-vp-divider bg-vp-bg px-3 py-2 text-vp-text-1 outline-none focus:border-vp-brand-1"
                            required
                        >
                        @error('password')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <button
                        type="submit"
                        class="inline-flex h-10 w-full items-center justify-center rounded-full bg-vp-brand-3 px-5 text-sm font-medium text-white transition-colors hover:bg-vp-brand-2"
                    >
                        {{ __('voodbuilder::gate.password.submit') }}
                    </button>
                </form>
            @endif
        </div>
    </div>
</div>

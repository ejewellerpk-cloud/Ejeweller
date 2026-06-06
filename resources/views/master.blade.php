<!DOCTYPE html>
<html dir="ltr" lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <!-- REQUIRED META TAGS -->
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <link rel="preconnect" href="{{ config('app.url') }}" crossorigin>
    <link rel="dns-prefetch" href="{{ config('app.url') }}">

    <script>
        (function () {
            var path = window.location.pathname || '';
            var isAdmin = path.indexOf('/admin') === 0 || path === '/exception';
            document.documentElement.classList.add(isAdmin ? 'boot-admin' : 'boot-storefront');
        })();
    </script>
    <style>
        html.boot-admin:not(.app-ready) #app {
            visibility: hidden;
        }

        html.boot-admin:not(.app-ready) body {
            background: #f7f7fc;
        }

        html.boot-storefront:not(.app-ready) body {
            background: #ffffff;
        }

        .home-hero-shell {
            width: 100%;
            overflow: hidden;
            margin-bottom: 2.5rem;
            aspect-ratio: 1689 / 600;
            contain: layout style paint;
        }

        @media (min-width: 640px) {
            .home-hero-shell {
                margin-bottom: 5rem;
            }
        }

        .home-hero-shell__img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }

        html.app-ready .home-hero-shell {
            display: none;
        }

        .home-static-chrome {
            contain: layout style;
        }

        html.app-ready .home-static-chrome {
            display: none;
        }

        .home-static-header {
            position: sticky;
            top: 0;
            z-index: 30;
            width: 100%;
            margin-bottom: 1.25rem;
            background: #ffffff;
            box-shadow: 0 1px 2px rgba(15, 23, 42, 0.06);
            padding: 0.75rem 0.75rem;
            padding-top: calc(0.75rem + env(safe-area-inset-top, 0px));
        }

        .home-static-header__inner {
            max-width: 72rem;
            margin: 0 auto;
            display: flex;
            align-items: center;
            min-height: 2.5rem;
        }

        .home-static-header__logo {
            width: 6rem;
            max-height: 2.5rem;
            object-fit: contain;
            display: block;
        }

        @media (min-width: 640px) {
            .home-static-header {
                margin-bottom: 2rem;
                padding-left: 1rem;
                padding-right: 1rem;
            }

            .home-static-header__logo {
                width: 7rem;
            }
        }
    </style>

    <!-- CUSTOM STYLE -->
    @vite('resources/css/app.css')
    <link rel="stylesheet" href="{{ asset('themes/default/css/custom.css') }}">
    <!-- PAGE TITLE & SEO META -->
    @if(!empty($seoTitle))
        <title>{{ $seoTitle }} - {{ Settings::group('company')->get('company_name') }}</title>
        <meta name="description" content="{{ $seoDescription }}">
        <meta property="og:title" content="{{ $seoTitle }}">
        <meta property="og:description" content="{{ $seoDescription }}">
        <meta property="og:image" content="{{ $seoImage }}">
        <meta property="og:url" content="{{ url()->current() }}">
        <meta property="og:type" content="product">
        <meta name="twitter:card" content="summary_large_image">
        <meta name="twitter:title" content="{{ $seoTitle }}">
        <meta name="twitter:description" content="{{ $seoDescription }}">
        <meta name="twitter:image" content="{{ $seoImage }}">
    @else
        <title>{{ Settings::group('company')->get('company_name') }}</title>
    @endif

    @if (!empty($isHomepage) && !empty($heroPreloadImage))
        <link rel="preload" as="image" href="{{ $heroPreloadImage }}" fetchpriority="high">
    @endif

    <!-- FAV ICON -->
    <link rel="icon" type="image/png" href="{{ $favicon }}?v={{ time() }}">


    @if (!blank($analytics))
        @foreach ($analytics as $analytic)
            @if (!blank($analytic->analyticSections))
                @foreach ($analytic->analyticSections as $section)
                    @if ($section->section == \App\Enums\AnalyticSection::HEAD)
                        {!! $section->data !!}
                    @endif
                @endforeach
            @endif
        @endforeach
    @endif

    @php
        $analyticsSite = \App\Analytics\Models\AnalyticsSite::query()
            ->where('is_active', true)
            ->orderByDesc('updated_at')
            ->first(['public_key']);
        $analyticsPublicKey = $analyticsSite?->public_key;
        $pkOk = is_string($analyticsPublicKey)
            && str_starts_with($analyticsPublicKey, 'pk_')
            && strlen($analyticsPublicKey) >= 20
            && !str_contains($analyticsPublicKey, '...');
        if (!$pkOk) {
            $analyticsPublicKey = trim((string) env('ANALYTICS_PUBLIC_KEY', ''));
            $pkOk = is_string($analyticsPublicKey)
                && str_starts_with($analyticsPublicKey, 'pk_')
                && strlen($analyticsPublicKey) >= 20
                && !str_contains($analyticsPublicKey, '...');
        }
    @endphp
    @if (config('analytics.enabled', true) && $pkOk)
        <script>
            window.__ANALYTICS__ = {
                siteKey: @json($analyticsPublicKey),
                endpoint: @json(config('analytics.tracker.collect_url') ?? url('/api/analytics/v1/collect')),
                userId: @json(auth()->id()),
                batchSize: {{ (int) config('analytics.tracker.batch_size', 20) }},
                flushInterval: {{ (int) config('analytics.tracker.flush_interval_ms', 3000) }}
            };
        </script>
        <script async src="{{ asset(config('analytics.tracker.cdn_url', '/analytics/tracker.js')) }}"></script>
        @if (config('analytics_enterprise.enabled', true) && config('analytics_enterprise.features.heatmaps', true))
        <script async src="{{ asset('/analytics/intelligence-tracker.js') }}"></script>
        @endif
    @endif
    @laravelPWA
    <script>
        if ('serviceWorker' in navigator) {
            let refreshing = false;
            let hasController = !!navigator.serviceWorker.controller;
            navigator.serviceWorker.addEventListener('controllerchange', () => {
                if (!hasController) {
                    hasController = true;
                    return;
                }
                if (!refreshing) {
                    refreshing = true;
                    window.location.reload();
                }
            });
        }
    </script>
</head>

<body>
    @if (!blank($analytics))
        @foreach ($analytics as $analytic)
            @if (!blank($analytic->analyticSections))
                @foreach ($analytic->analyticSections as $section)
                    @if ($section->section == \App\Enums\AnalyticSection::BODY)
                        {!! $section->data !!}
                    @endif
                @endforeach
            @endif
        @endforeach
    @endif

    @if (!empty($isHomepage))
        <div id="home-static-chrome" class="home-static-chrome">
            @if (!empty($themeLogo))
                <header class="home-static-header">
                    <div class="home-static-header__inner">
                        <img class="home-static-header__logo" src="{{ $themeLogo }}" alt="logo" width="128" height="40" decoding="async">
                    </div>
                </header>
            @endif

            @if (!empty($heroPreloadImage))
                <div id="home-hero-shell" class="home-hero-shell" aria-hidden="true">
                    <img
                        class="home-hero-shell__img"
                        src="{{ $heroPreloadImage }}"
                        alt="banner"
                        width="1689"
                        height="600"
                        fetchpriority="high"
                        decoding="async"
                    >
                </div>
            @endif
        </div>
    @endif

    <div id="app"></div>

    @if (!blank($analytics))
        @foreach ($analytics as $analytic)
            @if (!blank($analytic->analyticSections))
                @foreach ($analytic->analyticSections as $section)
                    @if ($section->section == \App\Enums\AnalyticSection::FOOTER)
                        {!! $section->data !!}
                    @endif
                @endforeach
            @endif
        @endforeach
    @endif

    <script>
        window.APP_URL = "{{ config('app.url') }}";
        window.APP_DEMO = "{{ config('app.demo') }}";
        window.APP_KEY = "{{ config('app.api_key') }}";
        window.FACEBOOK_PIXEL_ID = "{{ Settings::group('site')->get('site_facebook_pixel_id') }}";
        window.FACEBOOK_PIXEL_CURRENCY = "{{ \App\Models\Currency::find(Settings::group('site')->get('site_default_currency'))?->code ?? 'PKR' }}";
        @if (!empty($heroSliders))
        window.__HOME_HERO_SLIDERS__ = @json($heroSliders);
        @endif
        @if (!empty($homeCategories))
        window.__HOME_CATEGORIES__ = @json($homeCategories);
        @endif
        @if (!empty($homePromotions))
        window.__HOME_PROMOTIONS__ = @json($homePromotions);
        @endif
    </script>
    @vite('resources/js/app.js')



    <script src="{{ asset('themes/default/js/modal.js') }}"></script>
    <script src="{{ asset('themes/default/js/customScript.js') }}"></script>
    <script src="{{ asset('themes/default/js/tabs.js') }}"></script>

</body>

</html>

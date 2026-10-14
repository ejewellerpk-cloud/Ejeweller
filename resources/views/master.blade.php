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
        html:not(.app-ready) body {
            background: #ffffff;
        }

        html.boot-admin:not(.app-ready) body {
            background: #f7f7fc;
        }

        /*
         * Storefront: paint LCP hero from HTML immediately.
         * Keep #app hidden until Vue is ready so we do not double-paint hero (CLS).
         * Admin: no boot chrome — show blank body until mount.
         */
        html.boot-storefront:not(.app-ready) #app {
            display: none !important;
        }

        #boot-chrome {
            display: none;
        }

        html.boot-storefront:not(.app-ready) #boot-chrome {
            display: block;
        }

        html.app-ready #boot-chrome {
            display: none !important;
        }

        #boot-chrome .boot-header-spacer {
            height: 72px;
            border-bottom: 1px solid #f3f4f6;
            background: #ffffff;
        }

        #boot-lcp-hero {
            margin: 1rem auto 2.5rem;
            padding-left: 1rem;
            padding-right: 1rem;
            max-width: 1280px;
        }

        #boot-lcp-hero .boot-lcp-shell {
            aspect-ratio: 1689 / 600;
            width: 100%;
            overflow: hidden;
            border-radius: 1rem;
            background: #f9fafb;
            border: 1px solid #f3f4f6;
        }

        #boot-lcp-hero img {
            display: block;
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        @media (min-width: 640px) {
            #boot-chrome .boot-header-spacer {
                height: 88px;
            }
            #boot-lcp-hero {
                margin-bottom: 3rem;
            }
        }
    </style>

    <!-- CUSTOM STYLE -->
    @vite('resources/css/app.css')
    <link rel="stylesheet" href="{{ asset('themes/default/css/custom.css') }}" media="print" onload="this.media='all'">
    <noscript><link rel="stylesheet" href="{{ asset('themes/default/css/custom.css') }}"></noscript>
    <!-- PAGE TITLE & SEO META -->
    @php
        $siteName = Settings::group('company')->get('company_name') ?: config('app.name');
        $defaultSeoDescription = trim((string) ($seoDescription ?? '')) !== ''
            ? $seoDescription
            : ($siteName . ' — Shop authentic jewelry online with secure checkout and fast delivery across Pakistan.');
    @endphp
    @if(!empty($seoTitle))
        <title>{{ $seoTitle }} - {{ $siteName }}</title>
        <meta name="description" content="{{ $seoDescription ?: $defaultSeoDescription }}">
        <meta property="og:title" content="{{ $seoTitle }}">
        <meta property="og:description" content="{{ $seoDescription ?: $defaultSeoDescription }}">
        <meta property="og:image" content="{{ $seoImage }}">
        <meta property="og:url" content="{{ url()->current() }}">
        <meta property="og:type" content="product">
        <meta name="twitter:card" content="summary_large_image">
        <meta name="twitter:title" content="{{ $seoTitle }}">
        <meta name="twitter:description" content="{{ $seoDescription ?: $defaultSeoDescription }}">
        <meta name="twitter:image" content="{{ $seoImage }}">
    @else
        <title>{{ $siteName }}</title>
        <meta name="description" content="{{ $defaultSeoDescription }}">
        <meta property="og:title" content="{{ $siteName }}">
        <meta property="og:description" content="{{ $defaultSeoDescription }}">
        <meta property="og:type" content="website">
        <meta property="og:url" content="{{ url()->current() }}">
        <meta name="twitter:card" content="summary">
        <meta name="twitter:title" content="{{ $siteName }}">
        <meta name="twitter:description" content="{{ $defaultSeoDescription }}">
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

    <div id="boot-chrome" aria-hidden="true">
        <div class="boot-header-spacer"></div>
        @if (!empty($isHomepage) && !empty($heroPreloadImage))
            <div id="boot-lcp-hero">
                <div class="boot-lcp-shell">
                    <img
                        src="{{ $heroPreloadImage }}"
                        alt=""
                        width="900"
                        height="320"
                        fetchpriority="high"
                        decoding="async"
                    >
                </div>
            </div>
        @endif
    </div>

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
    </script>
    @php
        $bootAdminApp = request()->is('admin') || request()->is('admin/*') || request()->is('exception');
    @endphp
    @vite($bootAdminApp ? 'resources/js/admin.js' : 'resources/js/storefront.js')



    <script src="{{ asset('themes/default/js/modal.js') }}" defer></script>
    <script src="{{ asset('themes/default/js/customScript.js') }}" defer></script>
    <script src="{{ asset('themes/default/js/tabs.js') }}" defer></script>

</body>

</html>

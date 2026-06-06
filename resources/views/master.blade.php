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
        html:not(.app-ready) #app {
            visibility: hidden;
        }

        html:not(.app-ready) body {
            background: #ffffff;
        }

        html.boot-admin:not(.app-ready) body {
            background: #f7f7fc;
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
    </script>
    @vite('resources/js/app.js')



    <script src="{{ asset('themes/default/js/modal.js') }}"></script>
    <script src="{{ asset('themes/default/js/customScript.js') }}"></script>
    <script src="{{ asset('themes/default/js/tabs.js') }}"></script>

</body>

</html>

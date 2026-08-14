<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $company['company_name'] ?? 'Payment' }}</title>
    <link rel="icon" href="{{ $faviconLogo->faviconLogo ?? '' }}">
    @vite('resources/css/app.css')
</head>
<body class="bg-[#F7F7FC]">
<div class="py-14 px-4 w-full max-w-lg mx-auto">
    <a href="{{ route('home') }}" class="block mx-auto w-36 mb-8">
        <img class="w-full" src="{{ $logo->logo ?? '' }}" alt="logo">
    </a>

    <div class="bg-white rounded-2xl border border-gray-200 p-6 space-y-4">
        <h1 class="text-xl font-extrabold text-gray-900">Complete {{ $paymentGateway->name }} payment</h1>
        <p class="text-sm text-gray-600">Order {{ $order->order_serial_no }} &middot; PKR {{ number_format((float) $order->total, 2) }}</p>

        @if ($record->consumer_number)
            <div class="rounded-xl bg-gray-50 border border-gray-200 p-4">
                <p class="text-xs font-bold uppercase tracking-wide text-gray-500 mb-1">1Bill consumer number</p>
                <p class="text-2xl font-black tracking-wide text-gray-900 select-all">{{ $record->consumer_number }}</p>
                <p class="mt-2 text-sm text-gray-600">
                    Pay this bill from JazzCash, Easypaisa, or any 1Bill partner. This page updates automatically after payment.
                </p>
            </div>
        @else
            <p class="text-sm text-gray-600">
                Confirm the payment on your {{ $paymentGateway->name }} app if you received an OTP or payment request.
                This page updates automatically.
            </p>
        @endif

        <p id="swich-status" class="text-sm font-semibold text-primary">Waiting for payment confirmation…</p>
        <a class="block text-center text-sm font-bold text-primary" href="{{ url('/checkout/payment') }}">Back to checkout</a>
    </div>
</div>
<script>
    const statusUrl = @json(route('payment.swich.status', ['paymentGateway' => $paymentGateway->slug, 'order' => $order]));
    async function poll() {
        try {
            const res = await fetch(statusUrl, { headers: { 'Accept': 'application/json' } });
            const data = await res.json();
            if (data.status === 'paid' && data.redirect) {
                window.location.href = data.redirect;
                return;
            }
            if (data.status === 'failed' || data.status === 'terminated' || data.status === 'block') {
                document.getElementById('swich-status').textContent = 'Payment failed. Please go back and try again.';
                return;
            }
        } catch (e) {}
        setTimeout(poll, 5000);
    }
    setTimeout(poll, 4000);
</script>
</body>
</html>

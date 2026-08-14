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
<body class="bg-[#F5F4F1] min-h-screen">
@php
    $isBiller = $record->method === 'biller' || filled($record->consumer_number);
    $walletName = $record->method === 'easypaisa' ? 'EasyPaisa' : 'JazzCash';
@endphp
<div class="py-12 px-4 w-full max-w-lg mx-auto">
    <a href="{{ route('home') }}" class="block mx-auto w-36 mb-8">
        <img class="w-full" src="{{ $logo->logo ?? '' }}" alt="logo">
    </a>

    <div class="rounded-3xl bg-white border border-[#E8E4DC] shadow-[0_18px_50px_rgba(31,31,57,0.08)] overflow-hidden">
        <div class="bg-heading text-white px-6 py-5">
            <p class="text-xs uppercase tracking-[0.18em] text-white/70 mb-1">Waiting for payment</p>
            <h1 class="text-xl font-extrabold">
                @if ($isBiller)
                    Pay your 1Bill voucher
                @else
                    Approve {{ $walletName }} request
                @endif
            </h1>
        </div>

        <div class="p-6 space-y-5">
            <div class="flex items-center justify-between text-sm">
                <span class="text-paragraph">Order</span>
                <span class="font-bold text-heading">{{ $order->order_serial_no }}</span>
            </div>
            <div class="flex items-center justify-between text-sm">
                <span class="text-paragraph">Amount</span>
                <span class="text-xl font-black text-heading">PKR {{ number_format((float) $order->total, 2) }}</span>
            </div>
            @if ($record->msisdn)
                <div class="flex items-center justify-between text-sm">
                    <span class="text-paragraph">Wallet number</span>
                    <span class="font-bold text-heading">{{ $record->msisdn }}</span>
                </div>
            @endif

            @if ($isBiller && $record->consumer_number)
                <div class="rounded-2xl bg-primary-slate border border-primary/20 p-5 text-center">
                    <p class="text-xs font-bold uppercase tracking-wide text-paragraph mb-2">1Bill consumer number (PSID)</p>
                    <p class="text-2xl font-black tracking-wide text-heading select-all break-all">{{ $record->consumer_number }}</p>
                    <p class="mt-3 text-sm text-paragraph">
                        Pay this PSID from JazzCash, EasyPaisa, or any 1Bill partner. This page waits until Swich confirms payment.
                    </p>
                </div>
            @else
                <div class="rounded-2xl bg-[#F7F7FC] border border-[#E8E4DC] p-5 space-y-3">
                    <div class="flex justify-center">
                        <span class="inline-block h-10 w-10 rounded-full border-4 border-primary/20 border-t-primary animate-spin"></span>
                    </div>
                    <p class="text-sm font-bold text-heading text-center">{{ $walletName }} par payment request chali gayi hai.</p>
                    <p class="text-sm text-paragraph text-center">
                        Apne {{ $walletName }} app mein OTP / payment request <strong>Approve</strong> karein.
                        Yeh page band na karein — confirmation ke baad order automatically paid ho jayega.
                    </p>
                </div>
            @endif

            <p id="swich-status" class="text-sm font-bold text-primary text-center">Waiting for Swich confirmation…</p>
            <a class="block text-center text-sm font-bold text-primary" href="{{ url('/checkout/payment') }}">Back to checkout</a>
        </div>
    </div>
</div>
<script>
    const statusUrl = @json(route('payment.swich.status', ['order' => $order]));
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
            document.getElementById('swich-status').textContent = 'Still waiting — approve the request in your wallet app.';
        } catch (e) {}
        setTimeout(poll, 3000);
    }
    setTimeout(poll, 2500);
</script>
</body>
</html>

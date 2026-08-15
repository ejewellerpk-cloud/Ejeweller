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
    $isBiller = $record->method === 'biller';
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

            <div id="swich-biller" class="rounded-2xl bg-primary-slate border border-primary/20 p-5 text-center {{ $isBiller && filled($record->consumer_number) ? '' : 'hidden' }}">
                <p class="text-xs font-bold uppercase tracking-wide text-paragraph mb-2">1Bill consumer number (PSID)</p>
                <p id="swich-psid" class="text-2xl font-black tracking-wide text-heading select-all break-all">{{ $record->consumer_number }}</p>
                <p class="mt-3 text-sm text-paragraph">
                    Pay this PSID from JazzCash, EasyPaisa, or any 1Bill partner. This page waits until Swich confirms payment.
                </p>
            </div>

            <div id="swich-wallet" class="rounded-2xl bg-[#F7F7FC] border border-[#E8E4DC] p-5 space-y-3 {{ $isBiller && filled($record->consumer_number) ? 'hidden' : '' }}">
                <div class="flex justify-center">
                    <span class="inline-block h-10 w-10 rounded-full border-4 border-primary/20 border-t-primary animate-spin"></span>
                </div>
                <p class="text-sm font-bold text-heading text-center">{{ $walletName }} par payment request bheji ja rahi hai.</p>
                <p class="text-sm text-paragraph text-center">
                    Request aate hi apne {{ $walletName }} app mein <strong>Approve</strong> karein.
                    Yeh page band na karein.
                </p>
            </div>

            <p id="swich-status" class="text-sm font-bold text-primary text-center">Sending request to {{ $walletName }}…</p>
            <a class="block text-center text-sm font-bold text-primary" href="{{ url('/checkout/payment') }}">Back to checkout</a>
        </div>
    </div>
</div>
<script>
    const statusUrl = @json(route('payment.swich.status', ['order' => $order]));
    const initiateUrl = @json(route('payment.swich.initiate', ['order' => $order]));
    const csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    const cancelledText = @json(trans('all.message.swich_payment_cancelled'));
    const paymentMethod = @json($record->method);
    let stopped = false;

    function showBiller(consumerNumber) {
        if (paymentMethod !== 'biller' || !consumerNumber) {
            return;
        }
        document.getElementById('swich-psid').textContent = consumerNumber;
        document.getElementById('swich-biller').classList.remove('hidden');
        document.getElementById('swich-wallet').classList.add('hidden');
    }

    function setStatus(text, isError) {
        const el = document.getElementById('swich-status');
        el.textContent = text;
        el.classList.toggle('text-danger', !!isError);
        el.classList.toggle('text-primary', !isError);
    }

    async function initiate() {
        try {
            const res = await fetch(initiateUrl, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrf,
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            const data = await res.json();
            showBiller(data.consumerNumber);
            if (data.status === 'cancelled' || data.status === 'canceled') {
                setStatus(data.message || cancelledText, true);
                stopped = true;
                return;
            }
            if (data.status === 'failed' || data.ok === false) {
                setStatus(data.message || 'Payment failed. Please go back and try again.', true);
                stopped = true;
                return;
            }
            setStatus('Request sent. Approve it in your {{ $walletName }} app.', false);
        } catch (e) {
            setStatus('Still connecting to {{ $walletName }}…', false);
        }
        poll();
    }

    async function poll() {
        if (stopped) return;
        try {
            const res = await fetch(statusUrl, { headers: { 'Accept': 'application/json' } });
            const data = await res.json();
            if (data.status === 'paid' && data.redirect) {
                window.location.href = data.redirect;
                return;
            }
            if (data.status === 'cancelled' || data.status === 'canceled') {
                setStatus(data.message || cancelledText, true);
                return;
            }
            if (data.status === 'failed' || data.status === 'terminated' || data.status === 'block') {
                setStatus('Payment failed. Please go back and try again.', true);
                return;
            }
            showBiller(data.consumerNumber);
        } catch (e) {}
        setTimeout(poll, 3000);
    }

    initiate();
</script>
</body>
</html>

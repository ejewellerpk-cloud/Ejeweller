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
    $isCancelled = in_array(strtolower((string) $record->status), ['cancelled', 'canceled', 'declined', 'rejected'], true);
    $isRequested = filled($record->swich_order_id) || filled($record->swich_transaction_id) || ($isBiller && filled($record->consumer_number));
    $phase = $isCancelled ? 'cancelled' : ($isRequested ? 'waiting' : 'sending');
@endphp
<div class="py-12 px-4 w-full max-w-lg mx-auto">
    <a href="{{ route('home') }}" class="block mx-auto w-36 mb-8">
        <img class="w-full" src="{{ $logo->logo ?? '' }}" alt="logo">
    </a>

    <div class="rounded-3xl bg-white border border-[#E8E4DC] shadow-[0_18px_50px_rgba(31,31,57,0.08)] overflow-hidden">
        <div id="swich-header" class="bg-heading text-white px-6 py-5 {{ $phase === 'cancelled' ? 'hidden' : '' }}">
            <p id="swich-phase-label" class="text-xs uppercase tracking-[0.18em] text-white/70 mb-1">
                {{ $phase === 'waiting' ? 'Waiting for you' : 'Sending request' }}
            </p>
            <h1 id="swich-phase-title" class="text-xl font-extrabold">
                @if ($isBiller)
                    Pay your 1Bill voucher
                @else
                    Approve {{ $walletName }} request
                @endif
            </h1>
            <ol id="swich-steps" class="mt-4 grid grid-cols-3 gap-2 text-[10px] font-bold uppercase tracking-wide">
                <li id="step-sending" class="rounded-full px-2 py-1 text-center {{ $phase === 'sending' ? 'bg-white text-heading' : 'bg-white/15 text-white/80' }}">1. Sending</li>
                <li id="step-waiting" class="rounded-full px-2 py-1 text-center {{ $phase === 'waiting' ? 'bg-white text-heading' : 'bg-white/15 text-white/80' }}">2. Waiting</li>
                <li id="step-done" class="rounded-full px-2 py-1 text-center bg-white/15 text-white/80">3. Paid</li>
            </ol>
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

            <div id="swich-paid" class="rounded-2xl bg-green-50 border border-green-200 p-6 text-center hidden">
                <p class="text-3xl sm:text-4xl font-black text-green-700 leading-tight">Thank you</p>
                <p class="mt-2 text-lg font-bold text-heading">Payment received</p>
                <p class="mt-3 text-sm text-paragraph">Order <span class="font-black text-heading">{{ $order->order_serial_no }}</span></p>
            </div>

            <div id="swich-cancelled" class="rounded-2xl bg-red-50 border border-red-200 p-6 text-center {{ $isCancelled ? '' : 'hidden' }}">
                <p class="text-3xl sm:text-4xl font-black text-red-600 leading-tight">Payment cancelled</p>
                <p class="mt-3 text-sm text-paragraph">JazzCash / EasyPaisa ne request reject kar di.</p>
                <a href="{{ url('/checkout/checkout') }}" class="mt-5 inline-flex items-center justify-center w-full h-12 rounded-xl bg-primary text-white font-bold">Try again</a>
            </div>

            <div id="swich-biller" class="rounded-2xl bg-primary-slate border border-primary/20 p-5 {{ !$isCancelled && $isBiller && filled($record->consumer_number) ? '' : 'hidden' }}">
                <p class="text-xs font-bold uppercase tracking-wide text-paragraph mb-2 text-center">1Bill consumer number (PSID)</p>
                <p id="swich-psid" class="text-2xl font-black tracking-wide text-heading select-all break-all text-center">{{ $record->consumer_number }}</p>
                <ol class="mt-5 space-y-2 text-sm text-heading text-left list-decimal list-inside leading-6">
                    <li><strong>JazzCash</strong> application kholen.</li>
                    <li><strong>More</strong> par click karein.</li>
                    <li><strong>Corporate Payments</strong> open karein.</li>
                    <li><strong>1Bill</strong> mein yeh PSID paste karke Pay karein.</li>
                </ol>
                <p class="mt-4 text-xs text-paragraph text-center">Yeh page band na karein. Payment confirm hote hi order complete ho jayega.</p>
            </div>

            <div id="swich-wallet" class="rounded-2xl bg-[#F7F7FC] border border-[#E8E4DC] p-5 space-y-3 {{ $isCancelled || ($isBiller && filled($record->consumer_number)) ? 'hidden' : '' }}">
                <div id="swich-spinner" class="flex justify-center {{ $isRequested ? 'hidden' : '' }}">
                    <span class="inline-block h-10 w-10 rounded-full border-4 border-primary/20 border-t-primary animate-spin"></span>
                </div>
                <p id="swich-wallet-title" class="text-sm font-bold text-heading text-center">
                    {{ $isRequested ? $walletName . ' par payment request chali gayi hai.' : $walletName . ' par payment request bheji ja rahi hai.' }}
                </p>
                <p id="swich-wallet-help" class="text-sm text-paragraph text-center">
                    @if ($isRequested)
                        Apne {{ $walletName }} app mein <strong>Approve</strong> karein. Yeh page band na karein.
                    @else
                        Request aate hi apne {{ $walletName }} app mein <strong>Approve</strong> karein. Yeh page band na karein.
                    @endif
                </p>
            </div>

            <p id="swich-status" class="text-sm font-bold text-center {{ $isCancelled ? 'text-danger' : 'text-primary' }} {{ $isCancelled ? 'hidden' : '' }}">
                {{ $isRequested ? 'Waiting for you — approve in your ' . $walletName . ' app.' : 'Sending request to ' . $walletName . '…' }}
            </p>
            <a id="swich-back" class="block text-center text-sm font-bold text-primary {{ $isCancelled ? 'hidden' : '' }}" href="{{ url('/checkout/checkout') }}">Back to checkout</a>
        </div>
    </div>
</div>
<script>
    const statusUrl = @json(route('payment.swich.status', ['order' => $order]));
    const initiateUrl = @json(route('payment.swich.initiate', ['order' => $order]));
    const csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    const cancelledText = @json(trans('all.message.swich_payment_cancelled'));
    const paymentMethod = @json($record->method);
    const walletName = @json($walletName);
    let stopped = @json($isCancelled);
    let paidRedirect = null;

    function markStep(id, active) {
        const el = document.getElementById(id);
        el.classList.toggle('bg-white', active);
        el.classList.toggle('text-heading', active);
        el.classList.toggle('bg-white/15', !active);
        el.classList.toggle('text-white/80', !active);
    }

    function setPhase(phase) {
        if (phase === 'paid') {
            markStep('step-sending', false);
            markStep('step-waiting', false);
            markStep('step-done', true);
            document.getElementById('swich-phase-label').textContent = 'Paid';
            document.getElementById('swich-phase-title').textContent = 'Payment received';
            return;
        }
        if (phase === 'waiting') {
            markStep('step-sending', false);
            markStep('step-waiting', true);
            markStep('step-done', false);
            document.getElementById('swich-phase-label').textContent = 'Waiting for you';
            return;
        }
        markStep('step-sending', true);
        markStep('step-waiting', false);
        markStep('step-done', false);
        document.getElementById('swich-phase-label').textContent = 'Sending request';
    }

    function showBiller(consumerNumber) {
        if (stopped || paymentMethod !== 'biller' || !consumerNumber) {
            return;
        }
        document.getElementById('swich-psid').textContent = consumerNumber;
        document.getElementById('swich-biller').classList.remove('hidden');
        document.getElementById('swich-wallet').classList.add('hidden');
        setPhase('waiting');
        setStatus('Waiting for you — pay this PSID from JazzCash.', false);
    }

    function showRequestSent() {
        if (stopped || paymentMethod === 'biller') {
            return;
        }
        document.getElementById('swich-spinner').classList.add('hidden');
        document.getElementById('swich-wallet-title').textContent = walletName + ' par payment request chali gayi hai.';
        document.getElementById('swich-wallet-help').innerHTML = 'Apne ' + walletName + ' app mein <strong>Approve</strong> karein. Yeh page band na karein.';
        setPhase('waiting');
        setStatus('Waiting for you — approve in your ' + walletName + ' app.', false);
    }

    function showPaid(redirect) {
        stopped = true;
        paidRedirect = redirect;
        setPhase('paid');
        document.getElementById('swich-wallet').classList.add('hidden');
        document.getElementById('swich-biller').classList.add('hidden');
        document.getElementById('swich-status').classList.add('hidden');
        document.getElementById('swich-cancelled').classList.add('hidden');
        document.getElementById('swich-back').classList.add('hidden');
        document.getElementById('swich-paid').classList.remove('hidden');
        document.getElementById('swich-header').classList.remove('hidden');
        if (paidRedirect) {
            setTimeout(function () { window.location.href = paidRedirect; }, 2200);
        }
    }

    function showCancelled() {
        stopped = true;
        document.getElementById('swich-header').classList.add('hidden');
        document.getElementById('swich-wallet').classList.add('hidden');
        document.getElementById('swich-biller').classList.add('hidden');
        document.getElementById('swich-status').classList.add('hidden');
        document.getElementById('swich-paid').classList.add('hidden');
        document.getElementById('swich-back').classList.add('hidden');
        document.getElementById('swich-cancelled').classList.remove('hidden');
    }

    function setStatus(text, isError) {
        const el = document.getElementById('swich-status');
        el.textContent = text;
        el.classList.toggle('hidden', false);
        el.classList.toggle('text-danger', !!isError);
        el.classList.toggle('text-primary', !isError);
    }

    function isCancelled(status, message) {
        const s = String(status || '').toLowerCase();
        const m = (s + ' ' + String(message || '')).toLowerCase();
        if (['cancelled', 'canceled', 'declined', 'rejected'].includes(s)) {
            return true;
        }
        return m.includes('cancel') || m.includes('mpin') || m.includes('authorisation rejected')
            || m.includes('authorization rejected') || (m.includes('reject') && !m.includes('otp'));
    }

    function isFailed(status) {
        return ['failed', 'terminated', 'block', 'expired'].includes(String(status || '').toLowerCase());
    }

    async function initiate() {
        if (stopped) {
            return;
        }
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
            if (isCancelled(data.status) || data.phase === 'cancelled') {
                showCancelled();
                return;
            }
            if (data.status === 'paid' || data.phase === 'paid') {
                showPaid(data.redirect);
                return;
            }
            if (data.status === 'failed' || data.ok === false) {
                if (isCancelled(data.status, data.message)) {
                    showCancelled();
                    return;
                }
                stopped = true;
                setStatus(data.message || 'Payment failed. Please go back and try again.', true);
                document.getElementById('swich-spinner').classList.add('hidden');
                return;
            }
            showBiller(data.consumerNumber);
            showRequestSent();
        } catch (e) {
            setStatus('Still connecting to ' + walletName + '…', false);
        }
        poll();
    }

    async function poll() {
        if (stopped) return;
        try {
            const res = await fetch(statusUrl, { headers: { 'Accept': 'application/json' } });
            const data = await res.json();
            if (data.status === 'paid' || data.phase === 'paid') {
                showPaid(data.redirect);
                return;
            }
            if (data.phase === 'cancelled' || isCancelled(data.status) || isFailed(data.status)) {
                showCancelled();
                return;
            }
            if (data.phase === 'waiting' || data.consumerNumber || data.requested) {
                showBiller(data.consumerNumber);
                showRequestSent();
            }
        } catch (e) {}
        setTimeout(poll, 3000);
    }

    if (stopped) {
        showCancelled();
    } else {
        initiate();
    }
</script>
</body>
</html>

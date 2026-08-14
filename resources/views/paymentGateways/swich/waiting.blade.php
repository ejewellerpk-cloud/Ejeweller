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
<div class="py-12 px-4 w-full max-w-lg mx-auto">
    <a href="{{ route('home') }}" class="block mx-auto w-36 mb-8">
        <img class="w-full" src="{{ $logo->logo ?? '' }}" alt="logo">
    </a>

    <div class="rounded-3xl bg-white border border-[#E8E4DC] shadow-[0_18px_50px_rgba(31,31,57,0.08)] overflow-hidden">
        <div class="bg-heading text-white px-6 py-5">
            <p class="text-xs uppercase tracking-[0.18em] text-white/70 mb-1">Swich PayIn</p>
            <h1 class="text-xl font-extrabold">
                @if ($record->consumer_number)
                    Pay your 1Bill voucher
                @else
                    Confirm wallet OTP
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
                    <span class="text-paragraph">Mobile</span>
                    <span class="font-bold text-heading">{{ $record->msisdn }}</span>
                </div>
            @endif

            @if ($record->consumer_number)
                <div class="rounded-2xl bg-primary-slate border border-primary/20 p-5 text-center">
                    <p class="text-xs font-bold uppercase tracking-wide text-paragraph mb-2">1Bill consumer number (PSID)</p>
                    <p class="text-2xl font-black tracking-wide text-heading select-all break-all">{{ $record->consumer_number }}</p>
                    <p class="mt-3 text-sm text-paragraph">
                        Pay this PSID from JazzCash, EasyPaisa, or any 1Bill partner. Creating the voucher is not payment — this page waits until Swich reports success.
                    </p>
                </div>
            @else
                <div class="rounded-2xl bg-[#F7F7FC] border border-[#E8E4DC] p-5">
                    <p class="text-sm text-paragraph">
                        Approve the payment in your JazzCash or EasyPaisa app if you received an OTP or request. Do not close this page.
                    </p>
                </div>
            @endif

            <p id="swich-status" class="text-sm font-bold text-primary">Waiting for Swich confirmation…</p>
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
        } catch (e) {}
        setTimeout(poll, 5000);
    }
    setTimeout(poll, 4000);
</script>
</body>
</html>

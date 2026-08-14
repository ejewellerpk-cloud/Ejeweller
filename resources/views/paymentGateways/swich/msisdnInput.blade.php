@php
    $swichOptions = collect($paymentGateway->gatewayOptions ?? [])->pluck('value', 'option');
    $ewalletOn = (int) ($swichOptions['swich_ewallet_status'] ?? \App\Enums\Activity::DISABLE) === \App\Enums\Activity::ENABLE;
    $billerOn = (int) ($swichOptions['swich_biller_status'] ?? \App\Enums\Activity::DISABLE) === \App\Enums\Activity::ENABLE;
    $defaultEmail = old('email', $order->shippingAddress->email ?? $order->user->email ?? '');
    $rawPhone = old('msisdn', $order->shippingAddress->phone ?? '');
    $defaultPhone = \App\Http\PaymentGateways\Gateways\Swich::normalizeMsisdn(
        (string) $rawPhone,
        (string) ($order->shippingAddress->country_code ?? '')
    ) ?: $rawPhone;
    $defaultMethod = old('swich_method', $ewalletOn ? 'jazzcash' : 'biller');
    $amountLabel = number_format((float) $order->total, 2);
@endphp
<div id="{{ $paymentGateway->slug }}_div" class="hidden mb-6">
    <div class="rounded-3xl bg-white border border-[#E8E4DC] shadow-[0_18px_50px_rgba(31,31,57,0.08)] overflow-hidden">
        <div class="bg-heading text-white px-6 py-5 flex items-center justify-between gap-4">
            <div>
                <p class="text-xs uppercase tracking-[0.18em] text-white/70 mb-1">Swich PayIn</p>
                <h2 class="text-xl font-extrabold leading-tight">Pay with JazzCash, EasyPaisa or 1Bill</h2>
            </div>
            <div class="text-right shrink-0">
                <p class="text-xs text-white/70">Amount</p>
                <p class="text-2xl font-black">PKR {{ $amountLabel }}</p>
            </div>
        </div>

        <div class="p-6 space-y-6">
            <p class="text-sm text-paragraph">
                Order <span class="font-bold text-heading">{{ $order->order_serial_no }}</span>.
                Wallet payments send an OTP to this mobile number. 1Bill creates a PSID you pay from JazzCash, EasyPaisa, or a 1Bill partner. Credit is applied only after Swich confirms success.
            </p>

            <div>
                <p class="block mb-3 text-sm font-bold text-heading">Choose method</p>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    @if ($ewalletOn)
                        <label class="cursor-pointer">
                            <input class="peer sr-only" type="radio" name="swich_method" value="jazzcash" {{ $defaultMethod === 'jazzcash' ? 'checked' : '' }}>
                            <span class="flex flex-col h-full rounded-2xl border-2 border-[#E8E4DC] px-4 py-4 peer-checked:border-primary peer-checked:bg-primary-slate transition">
                                <span class="text-base font-extrabold text-heading">JazzCash</span>
                                <span class="mt-1 text-xs text-paragraph">E-Wallet · OTP · channel 10</span>
                            </span>
                        </label>
                        <label class="cursor-pointer">
                            <input class="peer sr-only" type="radio" name="swich_method" value="easypaisa" {{ $defaultMethod === 'easypaisa' ? 'checked' : '' }}>
                            <span class="flex flex-col h-full rounded-2xl border-2 border-[#E8E4DC] px-4 py-4 peer-checked:border-primary peer-checked:bg-primary-slate transition">
                                <span class="text-base font-extrabold text-heading">EasyPaisa</span>
                                <span class="mt-1 text-xs text-paragraph">E-Wallet · OTP · channel 8</span>
                            </span>
                        </label>
                    @endif
                    @if ($billerOn)
                        <label class="cursor-pointer">
                            <input class="peer sr-only" type="radio" name="swich_method" value="biller" {{ $defaultMethod === 'biller' || !$ewalletOn ? 'checked' : '' }}>
                            <span class="flex flex-col h-full rounded-2xl border-2 border-[#E8E4DC] px-4 py-4 peer-checked:border-primary peer-checked:bg-primary-slate transition">
                                <span class="text-base font-extrabold text-heading">1Bill / PSID</span>
                                <span class="mt-1 text-xs text-paragraph">Biller · channel 11</span>
                            </span>
                        </label>
                    @endif
                </div>
            </div>

            <div>
                <label for="swich_msisdn" class="block mb-2 text-sm font-bold text-heading">Mobile number</label>
                <input type="tel" inputmode="numeric" autocomplete="tel" name="msisdn" id="swich_msisdn" value="{{ $defaultPhone }}" placeholder="03XXXXXXXXX" class="w-full h-12 rounded-xl px-4 border border-[#D9DBE9] bg-white text-heading">
                <p class="mt-2 text-xs text-paragraph">Swich requires format <strong>03XXXXXXXXX</strong>. +92 and 92 numbers are converted automatically.</p>
            </div>

            <div>
                <label for="swich_email" class="block mb-2 text-sm font-bold text-heading">Email</label>
                <input type="email" name="email" id="swich_email" value="{{ $defaultEmail }}" required class="w-full h-12 rounded-xl px-4 border border-[#D9DBE9] bg-white text-heading">
            </div>
        </div>
    </div>
</div>
<script>
    (function () {
        function toSwichMsisdn(value) {
            let digits = String(value || '').replace(/\D+/g, '');
            const match = digits.match(/(?:92)?0?(3\d{9})$/);
            return match ? ('0' + match[1]) : digits;
        }
        const input = document.getElementById('swich_msisdn');
        const form = document.getElementById('paymentForm');
        if (input) {
            input.addEventListener('blur', function () {
                const next = toSwichMsisdn(input.value);
                if (/^03\d{9}$/.test(next)) input.value = next;
            });
        }
        if (form && input) {
            form.addEventListener('submit', function () {
                const next = toSwichMsisdn(input.value);
                if (next) input.value = next;
            });
        }
    })();
</script>

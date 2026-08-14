@php
    $swichOptions = collect($paymentGateway->gatewayOptions ?? [])->pluck('value', 'option');
    $ewalletOn = (int) ($swichOptions['swich_ewallet_status'] ?? \App\Enums\Activity::DISABLE) === \App\Enums\Activity::ENABLE;
    $billerOn = (int) ($swichOptions['swich_biller_status'] ?? \App\Enums\Activity::DISABLE) === \App\Enums\Activity::ENABLE;
    $defaultEmail = old('email', $order->shippingAddress->email ?? $order->user->email ?? '');
    $defaultPhone = old('msisdn', $order->shippingAddress->phone ?? '');
@endphp
<fieldset id="{{ $paymentGateway->slug }}_div" class="grid grid-cols-1 gap-4 mb-6 hidden">
    <div class="w-full">
        <p class="block mb-2 text-sm font-semibold text-gray-700">Payment method</p>
        <div class="flex flex-col gap-2">
            @if ($ewalletOn)
                <label class="flex items-center gap-2 text-sm font-medium">
                    <input type="radio" name="swich_method" value="jazzcash" {{ old('swich_method', 'jazzcash') === 'jazzcash' ? 'checked' : '' }}>
                    JazzCash wallet (OTP)
                </label>
                <label class="flex items-center gap-2 text-sm font-medium">
                    <input type="radio" name="swich_method" value="easypaisa" {{ old('swich_method') === 'easypaisa' ? 'checked' : '' }}>
                    EasyPaisa wallet (OTP)
                </label>
            @endif
            @if ($billerOn)
                <label class="flex items-center gap-2 text-sm font-medium">
                    <input type="radio" name="swich_method" value="biller" {{ old('swich_method') === 'biller' || !$ewalletOn ? 'checked' : '' }}>
                    1Bill / PSID
                </label>
            @endif
        </div>
    </div>
    <div class="w-full">
        <label for="swich_msisdn" class="block mb-2 text-sm font-semibold text-gray-700">Mobile number (03XXXXXXXXX)</label>
        <input type="tel" inputmode="numeric" name="msisdn" id="swich_msisdn" value="{{ $defaultPhone }}" placeholder="03XXXXXXXXX" class="w-full h-12 rounded-lg px-4 border border-[#D9DBE9]">
    </div>
    <div class="w-full">
        <label for="swich_email" class="block mb-2 text-sm font-semibold text-gray-700">Email</label>
        <input type="email" name="email" id="swich_email" value="{{ $defaultEmail }}" required class="w-full h-12 rounded-lg px-4 border border-[#D9DBE9]">
        <p class="mt-2 text-xs text-gray-500">Required by Swich. For 1Bill you will receive a PSID to pay from JazzCash, EasyPaisa, or a partner.</p>
    </div>
</fieldset>

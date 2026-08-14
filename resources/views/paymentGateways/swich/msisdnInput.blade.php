<fieldset id="{{ $paymentGateway->slug }}_div" class="grid grid-cols-1 gap-4 mb-6 hidden">
    <div class="w-full">
        <label for="{{ $paymentGateway->slug }}_msisdn" class="block mb-2 text-sm font-semibold text-gray-700">
            Wallet / JazzCash / Easypaisa mobile number
        </label>
        <input
            type="tel"
            inputmode="numeric"
            name="msisdn"
            id="{{ $paymentGateway->slug }}_msisdn"
            value="{{ old('msisdn', $order->shippingAddress->phone ?? '') }}"
            placeholder="03XXXXXXXXX"
            class="w-full h-12 rounded-lg px-4 border border-[#D9DBE9]"
        >
        <p class="mt-2 text-xs text-gray-500">Use the number registered on JazzCash or Easypaisa. Format: 03XXXXXXXXX</p>
    </div>
</fieldset>

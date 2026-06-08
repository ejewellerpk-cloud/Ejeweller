A new order has been placed on your store.

Order #{{ $order->order_serial_no }}
{{ $message }}

Customer: {{ $order->user?->name ?? 'Guest' }}
@if($order->user?->email)
Email: {{ $order->user->email }}
@endif
@if($order->user?->phone)
Phone: {{ $order->user->phone }}
@endif
Total: {{ App\Libraries\AppLibrary::currencyAmountFormat($order->total) }}
Payment: {{ $order->paymentMethod?->name ?? 'N/A' }}

@if($order->orderProducts && $order->orderProducts->count() > 0)
Items:
@foreach($order->orderProducts as $stock)
- {{ $stock->product?->name ?? 'Product' }} x{{ abs($stock->quantity) }} — {{ App\Libraries\AppLibrary::currencyAmountFormat($stock->total) }}
@endforeach
@endif

Open admin panel:
{{ rtrim(config('app.url'), '/') }}/admin/online-orders

{{ config('app.name') }}

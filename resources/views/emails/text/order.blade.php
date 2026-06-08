Hello {{ $name }},

We wanted to let you know there is a new update regarding your recent purchase.

Order #{{ $orderId }}
{{ $message }}

You can review your order history by signing in to your account:
{{ rtrim(config('app.url'), '/') }}

Thank you for shopping with us.
{{ config('app.name') }} Team

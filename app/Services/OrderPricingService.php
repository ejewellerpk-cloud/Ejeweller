<?php

namespace App\Services;

use Exception;
use App\Enums\Ask;
use App\Enums\Status;
use App\Models\Coupon;
use App\Models\Address;
use App\Models\Product;
use App\Enums\Activity;
use App\Enums\OrderType;
use App\Models\OrderArea;
use App\Enums\ShippingType;
use App\Models\OrderCoupon;
use App\Enums\DiscountType;
use App\Enums\ShippingMethod;
use App\Libraries\AppLibrary;
use App\Models\PaymentGateway;
use App\Models\ProductVariation;
use Dipokhalder\Settings\Facades\Settings;

/**
 * Server-side order pricing. Never trusts any price / tax / discount / shipping
 * value sent by the client; everything is recomputed from the database.
 */
class OrderPricingService
{
    private int $decimals;

    public function __construct()
    {
        $decimals       = env('CURRENCY_DECIMAL_POINT');
        $this->decimals = $decimals === null || $decimals === '' ? 2 : (int) $decimals;
    }

    /**
     * @param array    $data   products (json string|array), coupon_id, order_type, shipping_id, billing_id, guest_info (json string|array)
     * @param int|null $userId authenticated / resolved guest user id (used for coupon per-user limit & address ownership)
     * @return array{products: array, subtotal: float, discount: float, tax: float, shipping_charge: float, total: float, coupon_id: int|null, cod_fee: float}
     * @throws Exception
     */
    public function calculate(array $data, ?int $userId = null): array
    {
        $orderType = (int) ($data['order_type'] ?? 0);
        $lines     = $this->decodeProducts($data['products'] ?? null);

        $this->guardQuantities($lines);

        $resolved = [];
        $subtotal = 0.0;
        $taxTotal = 0.0;

        // Lock product rows so concurrent checkouts cannot both pass the stock check.
        $productIds = array_values(array_unique(array_map(fn($line) => (int) ($line['product_id'] ?? 0), $lines)));
        $products   = Product::whereIn('id', $productIds)->lockForUpdate()->get()->keyBy('id');

        $variationIds = array_values(array_unique(array_filter(array_map(
            fn($line) => (int) ($line['variation_id'] ?? 0),
            $lines
        ))));
        $variations   = $variationIds ? ProductVariation::whereIn('id', $variationIds)->get()->keyBy('id') : collect();

        // Aggregate requested quantity per purchasable item (same item may appear on several lines).
        $requestedByItem = [];
        foreach ($lines as $line) {
            $key                   = $this->itemKey($line);
            $requestedByItem[$key] = ($requestedByItem[$key] ?? 0) + (int) $line['quantity'];
        }

        foreach ($lines as $line) {
            $productId   = (int) $line['product_id'];
            $variationId = (int) ($line['variation_id'] ?? 0);
            $quantity    = (int) $line['quantity'];

            /** @var Product|null $product */
            $product = $products->get($productId);
            if (!$product) {
                throw new Exception('One of the products in your order is no longer available.', 422);
            }
            if ((int) $product->status !== Status::ACTIVE || (int) $product->can_purchasable !== Ask::YES) {
                throw new Exception($product->name . ' is not available for purchase.', 422);
            }

            $hasVariations = $product->variations()->exists();
            $variation     = null;

            if ($variationId > 0) {
                $variation = $variations->get($variationId);
                if (!$variation || (int) $variation->product_id !== $productId) {
                    throw new Exception('Invalid variation selected for ' . $product->name . '.', 422);
                }
                $basePrice = (float) $variation->price;
            } else {
                if ($hasVariations) {
                    throw new Exception('Please select a variation for ' . $product->name . '.', 422);
                }
                $basePrice = (float) $product->selling_price;
            }

            // Active offer discount (same rules as storefront resources).
            $unitPrice = AppLibrary::productOfferPrice(
                $basePrice,
                (float) $product->discount,
                $product->offer_start_date,
                $product->offer_end_date
            );
            $unitPrice = round($unitPrice, $this->decimals);
            if ($unitPrice < 0) {
                $unitPrice = 0.0;
            }

            // Maximum purchase quantity (0 / null = unlimited).
            $itemKey = $this->itemKey($line);
            $maxQty  = (int) $product->maximum_purchase_quantity;
            if ($maxQty > 0 && $requestedByItem[$itemKey] > $maxQty) {
                throw new Exception(
                    'Maximum purchase quantity for ' . $product->name . ' is ' . $maxQty . '.',
                    422
                );
            }

            // Available stock.
            if ($variation) {
                $available = (int) $product->show_stock_out == Activity::DISABLE
                    ? (int) $variation->stockItems()->sum('quantity')
                    : 0;
            } else {
                $available = (int) $product->stock;
            }
            if ($requestedByItem[$itemKey] > $available) {
                throw new Exception('Insufficient stock for ' . $product->name . '.', 422);
            }

            // Taxes (percentage of unit price, per unit, then multiplied by quantity - same as frontend).
            $taxes    = [];
            $unitTax  = 0.0;
            $taxRows  = $product->taxes()->with('tax')->get();
            foreach ($taxRows as $taxRow) {
                $tax = $taxRow->tax;
                if (!$tax || (int) $tax->status !== Status::ACTIVE) {
                    continue;
                }
                $rate = (float) $tax->tax_rate;
                if ($rate <= 0) {
                    continue;
                }
                $taxAmount = ($unitPrice / 100) * $rate;
                $unitTax  += $taxAmount;
                $taxes[]   = [
                    'id'         => $tax->id,
                    'name'       => $tax->name,
                    'code'       => $tax->code,
                    'tax_rate'   => $rate,
                    'tax_amount' => $taxAmount,
                ];
            }

            $lineSubtotal = round($unitPrice * $quantity, $this->decimals);
            $lineTax      = round($unitTax * $quantity, $this->decimals);
            $lineTotal    = round($lineSubtotal + $lineTax, $this->decimals);

            $subtotal += $lineSubtotal;
            $taxTotal += $lineTax;

            $resolved[] = [
                'product_id'      => $productId,
                'variation_id'    => $variation ? $variationId : 0,
                'variation_names' => isset($line['variation_names']) && is_string($line['variation_names'])
                    ? mb_substr($line['variation_names'], 0, 500)
                    : null,
                'sku'             => $variation ? ($variation->sku ?: $product->sku) : $product->sku,
                'quantity'        => $quantity,
                'price'           => $unitPrice,
                'discount'        => 0,
                'total_tax'       => $lineTax,
                'taxes'           => $taxes,
                'subtotal'        => $lineSubtotal,
                'total'           => $lineTotal,
                'shipping'        => [
                    'shipping_type'                => (int) $product->shipping_type,
                    'shipping_cost'                => (float) $product->shipping_cost,
                    'is_product_quantity_multiply' => (int) $product->is_product_quantity_multiply,
                ],
            ];
        }

        $subtotal = round($subtotal, $this->decimals);
        $taxTotal = round($taxTotal, $this->decimals);

        // Coupon.
        $couponId = (int) ($data['coupon_id'] ?? 0);
        $discount = 0.0;
        if ($couponId > 0) {
            $discount = $this->couponDiscount($couponId, $subtotal, $userId);
        } else {
            $couponId = 0;
        }

        // Shipping.
        $shippingCharge = 0.0;
        if ($orderType === OrderType::DELIVERY) {
            $this->assertAddressOwnership($data, $userId);
            $shippingCharge = $this->shippingCharge($resolved, $data, $userId);
        }

        $codFee = 0.0;
        $paymentMethodId = (int) ($data['payment_method'] ?? 0);
        if ($paymentMethodId > 0) {
            $gateway = PaymentGateway::find($paymentMethodId);
            if ($gateway && $gateway->slug === 'cashondelivery') {
                $codFee = max(0, (float) (Settings::group('site')->get('site_cod_fee') ?? 0));
            }
        }

        // COD fee is folded into shipping_charge so existing order columns stay compatible.
        $shippingCharge = round($shippingCharge + $codFee, $this->decimals);

        $total = round($subtotal - $discount + $taxTotal + $shippingCharge, $this->decimals);
        if ($total < 0) {
            $total = 0.0;
        }

        // Strip internal-only shipping info from returned product lines.
        $products = array_map(function ($line) {
            unset($line['shipping']);
            return $line;
        }, $resolved);

        return [
            'products'        => $products,
            'subtotal'        => $subtotal,
            'discount'        => $discount,
            'tax'             => $taxTotal,
            'shipping_charge' => $shippingCharge,
            'total'           => $total,
            'coupon_id'       => $couponId > 0 ? $couponId : null,
            'cod_fee'         => $codFee,
        ];
    }

    /**
     * @throws Exception
     */
    private function decodeProducts(mixed $products): array
    {
        if (is_string($products)) {
            $products = json_decode($products, true);
        } elseif (is_object($products)) {
            $products = json_decode(json_encode($products), true);
        }

        if (!is_array($products) || count($products) === 0) {
            throw new Exception('Your cart is empty.', 422);
        }

        $lines = [];
        foreach ($products as $line) {
            if (!is_array($line)) {
                throw new Exception('Invalid cart data.', 422);
            }
            $lines[] = $line;
        }

        return $lines;
    }

    /**
     * @throws Exception
     */
    private function guardQuantities(array &$lines): void
    {
        foreach ($lines as $index => $line) {
            $productId = $line['product_id'] ?? null;
            $quantity  = $line['quantity'] ?? null;
            if (!is_numeric($productId) || (int) $productId <= 0) {
                throw new Exception('Invalid product in cart.', 422);
            }
            if (!is_numeric($quantity) || (int) $quantity != $quantity || (int) $quantity < 1) {
                throw new Exception('Invalid product quantity.', 422);
            }
            $lines[$index]['product_id']   = (int) $productId;
            $lines[$index]['quantity']     = (int) $quantity;
            $lines[$index]['variation_id'] = (int) ($line['variation_id'] ?? 0);
        }
    }

    private function itemKey(array $line): string
    {
        $variationId = (int) ($line['variation_id'] ?? 0);
        return $variationId > 0 ? 'v:' . $variationId : 'p:' . (int) $line['product_id'];
    }

    /**
     * @throws Exception
     */
    private function couponDiscount(int $couponId, float $subtotal, ?int $userId): float
    {
        $coupon = Coupon::find($couponId);
        if (!$coupon) {
            throw new Exception(trans('all.message.coupon_not_exist'), 422);
        }

        $now = now();
        if ($coupon->start_date && $now->lt($coupon->start_date)) {
            throw new Exception(trans('all.message.coupon_date_expired'), 422);
        }
        if (!$coupon->end_date || $now->gt($coupon->end_date)) {
            throw new Exception(trans('all.message.coupon_date_expired'), 422);
        }

        if ((float) $coupon->minimum_order > $subtotal) {
            throw new Exception(
                trans('all.message.minimum_order_amount') . AppLibrary::convertAmountFormat($coupon->minimum_order),
                422
            );
        }

        $limit = (int) $coupon->limit_per_user;
        if ($limit > 0 && $userId) {
            $used = OrderCoupon::where(['user_id' => $userId, 'coupon_id' => $coupon->id])->count();
            if ($used >= $limit) {
                throw new Exception(trans('all.message.coupon_limit_exceeded'), 422);
            }
        }

        if ((int) $coupon->discount_type === DiscountType::FIXED) {
            $amount = (float) $coupon->discount;
        } else {
            $amount = ($subtotal * (float) $coupon->discount) / 100;
        }

        $maximum = (float) $coupon->maximum_discount;
        if ($maximum > 0 && $amount > $maximum) {
            $amount = $maximum;
        }
        if ($amount > $subtotal) {
            $amount = $subtotal;
        }

        return round(max($amount, 0), $this->decimals);
    }

    /**
     * @throws Exception
     */
    private function shippingCharge(array $lines, array $data, ?int $userId): float
    {
        $setting = Settings::group('shipping_setup');
        $method  = (int) $setting->get('shipping_setup_method');

        if ($method === ShippingMethod::FLAT_WISE) {
            return max(round((float) $setting->get('shipping_setup_flat_rate_wise_cost'), $this->decimals), 0);
        }

        if ($method === ShippingMethod::PRODUCT_WISE) {
            $total = 0.0;
            foreach ($lines as $line) {
                $shipping = $line['shipping'];
                if ($shipping['shipping_type'] === ShippingType::FLAT_RATE) {
                    $cost   = $shipping['shipping_cost'];
                    $total += $shipping['is_product_quantity_multiply'] === Ask::YES
                        ? $cost * $line['quantity']
                        : $cost;
                }
            }
            return max(round($total, $this->decimals), 0);
        }

        if ($method === ShippingMethod::AREA_WISE) {
            $address = $this->resolveShippingAddress($data, $userId);
            $default = max(round((float) $setting->get('shipping_setup_area_wise_default_cost'), $this->decimals), 0);
            if (!$address) {
                return $default;
            }

            $area = OrderArea::where('country', $address['country'] ?? null)
                ->where('state', $address['state'] ?? null)
                ->where('city', $address['city'] ?? null)
                ->first();

            return $area ? max(round((float) $area->shipping_cost, $this->decimals), 0) : $default;
        }

        return 0.0;
    }

    /**
     * Never price/ship against (or copy) another customer's saved address.
     *
     * @throws Exception
     */
    private function assertAddressOwnership(array $data, ?int $userId): void
    {
        foreach ([(int) ($data['shipping_id'] ?? 0), (int) ($data['billing_id'] ?? 0)] as $addressId) {
            if ($addressId > 0) {
                $owned = Address::find($addressId);
                if (!$owned || !$userId || (int) $owned->user_id !== (int) $userId) {
                    throw new Exception('Invalid address selected.', 422);
                }
            }
        }
    }

    /**
     * @return array{country: ?string, state: ?string, city: ?string}|null
     */
    private function resolveShippingAddress(array $data, ?int $userId): ?array
    {
        $shippingId = (int) ($data['shipping_id'] ?? 0);

        if ($shippingId > 0) {
            $address = Address::find($shippingId);
            if ($address) {
                return [
                    'country' => $address->country,
                    'state'   => $address->state,
                    'city'    => $address->city,
                ];
            }
        }

        $guestInfo = $data['guest_info'] ?? null;
        if (is_string($guestInfo)) {
            $guestInfo = json_decode($guestInfo, true);
        }
        if (is_array($guestInfo)) {
            return [
                'country' => $guestInfo['country'] ?? null,
                'state'   => $guestInfo['state'] ?? null,
                'city'    => $guestInfo['city'] ?? null,
            ];
        }

        return null;
    }
}

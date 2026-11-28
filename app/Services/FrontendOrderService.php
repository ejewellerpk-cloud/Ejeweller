<?php

namespace App\Services;

use Exception;
use App\Enums\Status;
use App\Models\Order;
use App\Models\Stock;
use App\Models\Outlet;
use App\Models\Address;
use App\Models\Product;
use App\Enums\Ask;
use App\Models\PaymentGateway;
use App\Enums\OrderType;
use App\Models\StockTax;
use App\Enums\AddressType;
use App\Enums\OrderStatus;
use App\Models\OrderCoupon;
use App\Enums\PaymentStatus;
use App\Events\SendOrderSms;
use App\Models\OrderAddress;
use App\Events\SendOrderMail;
use App\Events\SendOrderPush;
use App\Events\SendOrderGotMail;
use App\Events\SendOrderGotSms;
use App\Events\SendOrderGotPush;
use App\Models\ProductVariation;
use App\Models\OrderOutletAddress;
use App\Models\CapturePaymentNotification;
use App\Models\SwichPayinTransaction;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Http\Requests\OrderRequest;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\PaginateRequest;
use App\Http\Requests\OrderStatusRequest;
use App\Libraries\AppLibrary;
use App\Libraries\QueryExceptionLibrary;
use App\Models\User;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;
use Dipokhalder\Settings\Facades\Settings;

class FrontendOrderService
{ 
    public object $order;
    protected array $frontendOrderFilter = [
        'order_serial_no',
        'user_id',
        'total',
        'order_type',
        'order_datetime',
        'payment_method',
        'payment_status',
        'status',
        'active'
    ];

    protected array $exceptFilter = [
        'excepts'
    ];

    /**
     * @throws Exception
     */
    public function myOrder(PaginateRequest $request)
    {
        try {
            $requests            = $request->all();
            $method              = $request->get('paginate', 0) == 1 ? 'paginate' : 'get';
            $methodValue         = $request->get('paginate', 0) == 1 ? $request->get('per_page', 10) : '*';
            $frontendOrderColumn = $request->get('order_column') ?? 'id';
            $frontendOrderType   = $request->get('order_by') ?? 'desc';

            return Order::where('order_type', "!=", OrderType::POS)->where(function ($query) use ($requests) {
                $query->where('user_id', auth()->user()->id);
                foreach ($requests as $key => $request) {
                    if (in_array($key, $this->frontendOrderFilter)) {
                        if ($key === "status") {
                            $query->where($key, (int)$request);
                        } else {
                            $query->where($key, 'like', '%' . $request . '%');
                        }
                    }
                    if (in_array($key, $this->exceptFilter)) {
                        $explodes = explode('|', $request);
                        if (is_array($explodes)) {
                            foreach ($explodes as $explode) {
                                $query->where('status', '!=', $explode);
                            }
                        }
                    }
                }
            })->orderBy($frontendOrderColumn, $frontendOrderType)->$method(
                $methodValue
            );
        } catch (Exception $exception) {
            Log::info($exception->getMessage());
            throw new Exception(QueryExceptionLibrary::message($exception), 422);
        }
    }

    /**
     * @throws Exception
     */
    public function myOrderStore(OrderRequest $request): object
    {
        try {
            $paymentGateway = PaymentGateway::find($request->payment_method);
            $isCod = $paymentGateway && $paymentGateway->slug === 'cashondelivery';

            $idempotencyKey = trim((string) $request->input('idempotency_key', ''));
            if ($idempotencyKey !== '') {
                $existing = Order::where('idempotency_key', $idempotencyKey)->first();
                if ($existing) {
                    $existing->is_cod = $isCod;
                    return $existing;
                }
            }

            DB::transaction(function () use ($request, $isCod, $idempotencyKey) {
                if (auth('sanctum')->check()) {
                    $this->abandonUnpaidCheckoutOrders((int) auth('sanctum')->user()->id);
                }

                $guestCheckout = Settings::group('site')->get('site_guest_checkout');
                if (!auth('sanctum')->check() && $guestCheckout == \App\Enums\Activity::DISABLE) {
                    throw new Exception("Guest checkout is disabled.", 422);
                }

                $userId = auth('sanctum')->check() ? auth('sanctum')->user()->id : null;
                if (!$userId && $request->guest_info) {
                    $guestInfo = json_decode($request->guest_info);
                    $guestEmail = trim((string) ($guestInfo->email ?? ''));
                    $guestPhone = trim((string) ($guestInfo->phone ?? ''));
                    $user = $guestEmail !== ''
                        ? User::where('email', $guestEmail)->first()
                        : null;
                    if (!$user && $guestPhone !== '') {
                        $user = User::where('phone', $guestPhone)->first();
                    }

                    // SECURITY: only ever attach a guest order to an existing *Customer* account.
                    // If the email/phone belongs to staff (Admin/Manager/...), create a separate guest
                    // customer instead and do not reuse/modify the staff account.
                    $staffCollision = false;
                    if ($user && !$user->hasRole(\App\Enums\Role::CUSTOMER)) {
                        $staffCollision = true;
                        $user           = null;
                    }

                    if (!$user) {
                        // users.email / users.phone are unique: when they collide with a staff
                        // account the new guest keeps contact details on the order address only.
                        $newEmail = $guestEmail ?: null;
                        $newPhone = $guestPhone ?: null;
                        if ($staffCollision) {
                            if ($newEmail && User::where('email', $newEmail)->exists()) {
                                $newEmail = null;
                            }
                            if ($newPhone && User::where('phone', $newPhone)->exists()) {
                                $newPhone = null;
                            }
                        }
                        $user = User::create([
                            'name'              => $guestInfo->full_name,
                            'email'             => $newEmail,
                            'phone'             => $newPhone,
                            'username'          => explode('@', $newEmail ?: ('guest' . mt_rand()))[0] . mt_rand(),
                            'password'          => bcrypt(Str::random(10)),
                            'email_verified_at' => now(),
                            'status'            => \App\Enums\Status::ACTIVE,
                            'country_code'      => $guestInfo->country_code ?? '',
                            'is_guest'          => \App\Enums\Ask::YES,
                        ]);
                        $user->assignRole(\App\Enums\Role::CUSTOMER);
                    } elseif ($guestEmail !== '' && blank($user->email) && filter_var($guestEmail, FILTER_VALIDATE_EMAIL)) {
                        $user->email = $guestEmail;
                        if (blank($user->name) && !blank($guestInfo->full_name ?? null)) {
                            $user->name = $guestInfo->full_name;
                        }
                        $user->save();
                    }
                    $userId = $user->id;
                }

                // Persist guest checkout address into the customer's address book so the
                // next checkout can auto-select it (OrderAddress alone is order history only).
                if (
                    $userId
                    && (int) $request->order_type === OrderType::DELIVERY
                    && (int) ($request->shipping_id ?? 0) <= 0
                    && $request->guest_info
                ) {
                    $syncedAddress = $this->syncAddressBookFromGuestInfo(
                        (int) $userId,
                        json_decode($request->guest_info)
                    );
                    if ($syncedAddress) {
                        $request->merge([
                            'shipping_id' => $syncedAddress->id,
                            'billing_id'  => (int) ($request->billing_id ?? 0) > 0
                                ? $request->billing_id
                                : $syncedAddress->id,
                        ]);
                    }
                }

                // Server-side pricing: never trust client prices/totals/discount/tax/shipping.
                $pricing = app(OrderPricingService::class)->calculate(
                    [
                        'products'       => $request->products,
                        'coupon_id'      => $request->coupon_id,
                        'order_type'     => $request->order_type,
                        'shipping_id'    => $request->shipping_id,
                        'billing_id'     => $request->billing_id,
                        'guest_info'     => $request->guest_info,
                        'payment_method' => $request->payment_method,
                    ],
                    $userId ? (int) $userId : null
                );

                if ($isCod) {
                    $this->assertCodLimits((float) $pricing['total']);
                }

                $payload = array_merge($request->validated(), [
                    'subtotal'        => $pricing['subtotal'],
                    'discount'        => $pricing['discount'],
                    'tax'             => $pricing['tax'],
                    'shipping_charge' => $pricing['shipping_charge'],
                    'total'           => $pricing['total'],
                    'user_id'         => $userId,
                    'status'          => OrderStatus::PENDING,
                    'payment_status'  => PaymentStatus::UNPAID,
                    'order_datetime'  => date('Y-m-d H:i:s'),
                    'active'          => Ask::YES,
                ]);

                if ($idempotencyKey !== '') {
                    $payload['idempotency_key'] = $idempotencyKey;
                } else {
                    unset($payload['idempotency_key']);
                }

                $this->order = Order::create($payload);

                foreach ($pricing['products'] as $product) {
                    $stockId = Stock::create([
                        'product_id'      => $product['product_id'],
                        'model_type'      => Order::class,
                        'model_id'        => $this->order->id,
                        'item_type'       => $product['variation_id'] > 0 ? ProductVariation::class : Product::class,
                        'item_id'         => $product['variation_id'] > 0 ? $product['variation_id'] : $product['product_id'],
                        'variation_names' => $product['variation_names'],
                        'sku'             => $product['sku'] ?? null,
                        'price'           => $product['price'],
                        'quantity'        => -$product['quantity'],
                        'discount'        => $product['discount'],
                        'tax'             => number_format($product['total_tax'], (int)env('CURRENCY_DECIMAL_POINT'), '.', ''),
                        'subtotal'        => $product['subtotal'],
                        'total'           => $product['total'],
                        'status'          => $isCod ? Status::ACTIVE : Status::INACTIVE,
                    ]);
                    if (!empty($product['taxes'])) {
                        $productTaxArray = [];
                        foreach ($product['taxes'] as $tax) {
                            $productTaxArray[] = [
                                'stock_id'   => $stockId->id,
                                'product_id' => $product['product_id'],
                                'tax_id'     => $tax['id'],
                                'name'       => $tax['name'],
                                'code'       => $tax['code'],
                                'tax_rate'   => $tax['tax_rate'],
                                'tax_amount' => $tax['tax_amount'],
                                'created_at' => now(),
                                'updated_at' => now()
                            ];
                        }
                        StockTax::insert($productTaxArray);
                    }
                }

                $this->order->order_serial_no = date('dmy') . $this->order->id;
                $this->order->save();

                if ($request->order_type == OrderType::DELIVERY) {
                    $shippingAddress = Address::find($request->shipping_id);
                    $billingAddress  = Address::find($request->billing_id);
                    if ($shippingAddress) {
                        OrderAddress::create([
                            'order_id'     => $this->order->id,
                            'user_id'      => $shippingAddress->user_id,
                            'address_type' => AddressType::SHIPPING,
                            'full_name'    => $shippingAddress->full_name,
                            'email'        => $shippingAddress->email,
                            'country_code' => $shippingAddress->country_code,
                            'phone'        => $shippingAddress->phone,
                            'country'      => $shippingAddress->country,
                            'address'      => $shippingAddress->address,
                            'state'        => $shippingAddress->state,
                            'city'         => $shippingAddress->city,
                            'zip_code'     => $shippingAddress->zip_code,
                            'latitude'     => $shippingAddress->latitude,
                            'longitude'    => $shippingAddress->longitude,
                        ]);
                    } elseif ($request->guest_info) {
                        $guestInfo = json_decode($request->guest_info);
                        OrderAddress::create([
                            'order_id'     => $this->order->id,
                            'user_id'      => $this->order->user_id,
                            'address_type' => AddressType::SHIPPING,
                            'full_name'    => $guestInfo->full_name,
                            'email'        => $guestInfo->email,
                            'country_code' => $guestInfo->country_code,
                            'phone'        => $guestInfo->phone,
                            'country'      => $guestInfo->country,
                            'address'      => $guestInfo->address,
                            'state'        => $guestInfo->state,
                            'city'         => $guestInfo->city,
                            'zip_code'     => $guestInfo->zip_code,
                            'latitude'     => null,
                            'longitude'    => null,
                        ]);
                    }

                    if ($billingAddress) {
                        OrderAddress::create([
                            'order_id'     => $this->order->id,
                            'user_id'      => $shippingAddress->user_id,
                            'address_type' => AddressType::BILLING,
                            'full_name'    => $billingAddress->full_name,
                            'email'        => $billingAddress->email,
                            'country_code' => $billingAddress->country_code,
                            'phone'        => $billingAddress->phone,
                            'country'      => $billingAddress->country,
                            'address'      => $billingAddress->address,
                            'state'        => $billingAddress->state,
                            'city'         => $billingAddress->city,
                            'zip_code'     => $billingAddress->zip_code,
                            'latitude'     => $billingAddress->latitude,
                            'longitude'    => $billingAddress->longitude,
                        ]);
                    } elseif ($request->guest_info) {
                        $guestInfo = json_decode($request->guest_info);
                        OrderAddress::create([
                            'order_id'     => $this->order->id,
                            'user_id'      => $this->order->user_id,
                            'address_type' => AddressType::BILLING,
                            'full_name'    => $guestInfo->full_name,
                            'email'        => $guestInfo->email,
                            'country_code' => $guestInfo->country_code,
                            'phone'        => $guestInfo->phone,
                            'country'      => $guestInfo->country,
                            'address'      => $guestInfo->address,
                            'state'        => $guestInfo->state,
                            'city'         => $guestInfo->city,
                            'zip_code'     => $guestInfo->zip_code,
                            'latitude'     => null,
                            'longitude'    => null,
                        ]);
                    }
                } elseif ($request->order_type === OrderType::PICK_UP) {
                    $outletAddress = Outlet::find($request->outlet_id);
                    if ($outletAddress) {
                        OrderOutletAddress::create([
                            'order_id'     => $this->order->id,
                            'user_id'      => $this->order->user_id,
                            'name'         => $outletAddress->name,
                            'email'        => $outletAddress->email,
                            'phone'        => $outletAddress->phone,
                            'country_code' => $outletAddress->country_code,
                            'latitude'     => $outletAddress->latitude,
                            'longitude'    => $outletAddress->longitude,
                            'city'         => $outletAddress->city,
                            'state'        => $outletAddress->state,
                            'zip_code'     => $outletAddress->zip_code,
                            'address'      => $outletAddress->address,
                        ]);
                    }
                }

                if (!empty($pricing['coupon_id'])) {
                    OrderCoupon::create([
                        'order_id'  => $this->order->id,
                        'coupon_id' => $pricing['coupon_id'],
                        'user_id'   => $userId,
                        'discount'  => $pricing['discount']
                    ]);
                }
            });

            // Never block the checkout HTTP response on SMTP / SMS / FCM / Facebook CAPI.
            // QUEUE_CONNECTION=sync would otherwise run all of these inline before the user sees success.
            $orderId = (int) $this->order->id;
            dispatch(function () use ($orderId) {
                try {
                    $order = Order::find($orderId);
                    if ($order) {
                        app(\App\Services\FbCapiService::class)->sendPurchaseEvent($order);
                    }
                } catch (\Exception $e) {
                    Log::error('FB CAPI Dispatch Error: ' . $e->getMessage());
                }
            })->afterResponse();

            if ($isCod) {
                $this->dispatchOrderNotificationsAfterResponse($orderId, OrderStatus::PENDING, true);
                $this->order->is_cod = true;
                app(ProductSectionService::class)->clearHomeSectionsCache();
            }

            return $this->order;
        } catch (Exception $exception) {
            DB::rollBack();
            Log::info($exception->getMessage());
            throw new Exception(QueryExceptionLibrary::message($exception), 422);
        }
    }

    /**
     * @throws Exception
     */
    public function show(Order $order): Order|array
    {
        try {
            if ($order->user_id == Auth::user()->id) {
                return $order;
            }
            return [];
        } catch (Exception $exception) {
            Log::info($exception->getMessage());
            throw new Exception(QueryExceptionLibrary::message($exception), 422);
        }
    }

    /**
     * @throws Exception
     */
    public function changeStatus(Order $order, OrderStatusRequest $request): Order
    {
        try {
            if ($order->user_id == Auth::user()->id) {
                if ($request->status == OrderStatus::CANCELED) {
                    if ($order->status >= OrderStatus::CONFIRMED) {
                        throw new Exception(trans('all.message.order_confirmed'), 422);
                    } else {
                        if ($order->transaction) {
                            app(PaymentService::class)->cashBack(
                                $order,
                                'credit',
                                rand(111111111111111, 99999999999999)
                            );
                        }
                        $this->dispatchOrderNotificationsAfterResponse((int) $order->id, $request->status, false);

                        $stocks = Stock::where(['model_type' => Order::class, 'model_id' => $order->id])->get();
                        foreach ($stocks as $stock) {
                            $stock->status = Status::INACTIVE;
                            $stock->save();
                        };

                        $order->status = $request->status;
                        $order->save();
                    }
                }
            }
            return $order;
        } catch (Exception $exception) {
            Log::info($exception->getMessage());
            throw new Exception(QueryExceptionLibrary::message($exception), 422);
        }
    }

    /**
     * Fire mail/SMS/push after the HTTP response so order placement stays fast.
     */
    private function dispatchOrderNotificationsAfterResponse(int $orderId, mixed $status, bool $includeGotNotifications): void
    {
        dispatch(function () use ($orderId, $status, $includeGotNotifications) {
            try {
                SendOrderMail::dispatch(['order_id' => $orderId, 'status' => $status]);
                SendOrderSms::dispatch(['order_id' => $orderId, 'status' => $status]);
                SendOrderPush::dispatch(['order_id' => $orderId, 'status' => $status]);

                if ($includeGotNotifications) {
                    SendOrderGotMail::dispatch(['order_id' => $orderId]);
                    SendOrderGotSms::dispatch(['order_id' => $orderId]);
                    SendOrderGotPush::dispatch(['order_id' => $orderId]);
                }
            } catch (\Exception $e) {
                Log::error('Order Notification Dispatch Error: ' . $e->getMessage());
            }
        })->afterResponse();
    }

    private function abandonUnpaidCheckoutOrders(int $userId): void
    {
        // Drop unpaid online-payment drafts when the customer starts a fresh checkout.
        $orders = Order::where('user_id', $userId)
            ->where('payment_status', PaymentStatus::UNPAID)
            ->where('status', OrderStatus::PENDING)
            ->whereHas('paymentMethod', function ($payment) {
                $payment->where('slug', '!=', 'cashondelivery');
            })
            ->get();

        foreach ($orders as $order) {
            $hasAttempt = Schema::hasTable('swich_payin_transactions')
                && SwichPayinTransaction::where('order_id', $order->id)->exists();

            if ($hasAttempt) {
                $order->update([
                    'status' => OrderStatus::CANCELED,
                    'active' => Ask::YES,
                    'reason' => 'Payment not completed. Customer started a new checkout.',
                ]);
                continue;
            }

            $this->deleteDraftCheckoutOrder($order);
        }
    }

    /**
     * @throws Exception
     */
    private function assertCodLimits(float $total): void
    {
        $min = (float) (Settings::group('site')->get('site_cod_min_order') ?? 0);
        $max = (float) (Settings::group('site')->get('site_cod_max_order') ?? 0);

        if ($min > 0 && $total < $min) {
            throw new Exception(
                'Cash on delivery requires a minimum order of ' . AppLibrary::currencyAmountFormat($min) . '.',
                422
            );
        }

        if ($max > 0 && $total > $max) {
            throw new Exception(
                'Cash on delivery is limited to orders up to ' . AppLibrary::currencyAmountFormat($max) . '.',
                422
            );
        }
    }

    private function deleteDraftCheckoutOrder(Order $order): void
    {
        $stockIds = Stock::where('model_id', $order->id)
            ->where('model_type', Order::class)
            ->pluck('id');

        if ($stockIds->isNotEmpty()) {
            StockTax::whereIn('stock_id', $stockIds)->delete();
            Stock::whereIn('id', $stockIds)->delete();
        }

        OrderAddress::where('order_id', $order->id)->delete();
        OrderOutletAddress::where('order_id', $order->id)->delete();
        OrderCoupon::where('order_id', $order->id)->delete();
        Transaction::where('order_id', $order->id)->delete();
        CapturePaymentNotification::where('order_id', $order->id)->delete();
        $order->delete();
    }

    /**
     * Save (or reuse) guest checkout details in addresses so future checkouts auto-select them.
     */
    private function syncAddressBookFromGuestInfo(int $userId, $guestInfo): ?Address
    {
        if (!$guestInfo || $userId <= 0) {
            return null;
        }

        $payload = [
            'full_name'    => trim((string) ($guestInfo->full_name ?? '')),
            'email'        => trim((string) ($guestInfo->email ?? '')),
            'country_code' => trim((string) ($guestInfo->country_code ?? '')),
            'phone'        => trim((string) ($guestInfo->phone ?? '')),
            'country'      => trim((string) ($guestInfo->country ?? '')),
            'state'        => trim((string) ($guestInfo->state ?? '')),
            'city'         => trim((string) ($guestInfo->city ?? '')),
            'zip_code'     => trim((string) ($guestInfo->zip_code ?? '')),
            'address'      => trim((string) ($guestInfo->address ?? '')),
        ];

        if ($payload['full_name'] === '' || $payload['phone'] === '' || $payload['address'] === '') {
            return null;
        }

        $existing = Address::query()
            ->where('user_id', $userId)
            ->where('phone', $payload['phone'])
            ->where('address', $payload['address'])
            ->where('city', $payload['city'])
            ->where('state', $payload['state'])
            ->where('country', $payload['country'])
            ->first();

        if ($existing) {
            $existing->fill([
                'full_name'    => $payload['full_name'],
                'email'        => $payload['email'] ?: $existing->email,
                'country_code' => $payload['country_code'] ?: $existing->country_code,
                'zip_code'     => $payload['zip_code'] !== '' ? $payload['zip_code'] : $existing->zip_code,
            ]);
            $existing->save();

            return $existing;
        }

        return Address::create($payload + ['user_id' => $userId]);
    }
}

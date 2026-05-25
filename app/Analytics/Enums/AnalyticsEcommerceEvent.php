<?php

namespace App\Analytics\Enums;

enum AnalyticsEcommerceEvent: string
{
    case ProductViewed = 'product_viewed';
    case CategoryViewed = 'category_viewed';
    case SearchPerformed = 'search_performed';
    case AddToWishlist = 'add_to_wishlist';
    case RemoveWishlist = 'remove_wishlist';
    case AddToCart = 'add_to_cart';
    case RemoveFromCart = 'remove_from_cart';
    case CheckoutStarted = 'checkout_started';
    case CheckoutAbandoned = 'checkout_abandoned';
    case PaymentAttempted = 'payment_attempted';
    case CodSelected = 'cod_selected';
    case OrderPlaced = 'order_placed';
    case OrderConfirmed = 'order_confirmed';
    case Refund = 'refund';
    case RepeatPurchase = 'repeat_purchase';

    public function category(): AnalyticsEventCategory
    {
        return AnalyticsEventCategory::Ecommerce;
    }
}

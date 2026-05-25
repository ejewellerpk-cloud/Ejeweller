<?php

namespace App\Analytics\Enums;

enum TrafficSource: string
{
    case Direct = 'direct';
    case Organic = 'organic';
    case Paid = 'paid';
    case Social = 'social';
    case Referral = 'referral';
    case Email = 'email';
    case Facebook = 'facebook';
    case Tiktok = 'tiktok';
    case GoogleAds = 'google_ads';
    case Unknown = 'unknown';
}

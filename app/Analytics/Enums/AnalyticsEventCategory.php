<?php

namespace App\Analytics\Enums;

enum AnalyticsEventCategory: string
{
    case Page = 'page';
    case Ecommerce = 'ecommerce';
    case Engagement = 'engagement';
    case Acquisition = 'acquisition';
    case Replay = 'replay';
    case Heatmap = 'heatmap';
    case Performance = 'performance';
}

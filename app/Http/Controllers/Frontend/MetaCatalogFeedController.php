<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Services\MarketingCampaignService;
use Symfony\Component\HttpFoundation\Response;

class MetaCatalogFeedController extends Controller
{
    public function csv(MarketingCampaignService $campaigns): Response
    {
        $csv = $campaigns->catalogCsv();

        return response($csv, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'inline; filename="meta-catalog.csv"',
            'Cache-Control' => 'public, max-age=1800',
        ]);
    }
}

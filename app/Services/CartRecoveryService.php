<?php

namespace App\Services;

use App\Analytics\Models\AnalyticsEvent;
use App\Analytics\Models\AnalyticsSite;
use App\Analytics\Support\AnalyticsSchema;
use App\Jobs\SendCartRecoveryMailJob;
use App\Mail\CartRecoveryMail;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class CartRecoveryService
{
    public function enqueueFromAbandons(?int $siteId = null, int $lookbackHours = 72): array
    {
        if (!AnalyticsSchema::hasTable('analytics_recovery_campaigns')
            || !AnalyticsSchema::hasTable('analytics_recovery_queue')
            || !AnalyticsSchema::hasTable('analytics_events')) {
            return ['enqueued' => 0, 'message' => 'Recovery tables missing. Run enterprise migrations.'];
        }

        $siteId = $siteId ?: AnalyticsSite::query()->where('is_active', true)->orderBy('id')->value('id');
        if (!$siteId) {
            return ['enqueued' => 0, 'message' => 'No analytics site found.'];
        }

        $campaign = DB::table('analytics_recovery_campaigns')
            ->where('site_id', $siteId)
            ->where('is_active', true)
            ->orderBy('id')
            ->first();

        if (!$campaign) {
            $growth = app(\App\Analytics\Enterprise\Services\EnterpriseGrowthService::class);
            $growth->ensureDefaultCampaign($siteId);
            $campaign = DB::table('analytics_recovery_campaigns')
                ->where('site_id', $siteId)
                ->where('is_active', true)
                ->orderBy('id')
                ->first();
        }

        if (!$campaign) {
            return ['enqueued' => 0, 'message' => 'Could not create recovery campaign.'];
        }

        $delays = json_decode($campaign->delays_minutes ?? '[]', true) ?: [60];
        $firstDelay = (int) ($delays[0] ?? 60);

        $since = now()->subHours($lookbackHours);
        $events = AnalyticsEvent::query()
            ->where('site_id', $siteId)
            ->where('event_name', 'checkout_abandoned')
            ->where('occurred_at', '>=', $since)
            ->orderByDesc('occurred_at')
            ->limit(200)
            ->get();

        $enqueued = 0;
        foreach ($events as $event) {
            $props = is_array($event->properties) ? $event->properties : (json_decode($event->properties ?? '{}', true) ?: []);
            $email = $props['email'] ?? null;
            $phone = $props['phone'] ?? null;
            $userId = $event->user_id;

            if (!$email && $userId) {
                $email = DB::table('users')->where('id', $userId)->value('email');
                $phone = $phone ?: DB::table('users')->where('id', $userId)->value('phone');
            }

            if (!$email && !$phone) {
                continue;
            }

            $exists = DB::table('analytics_recovery_queue')
                ->where('site_id', $siteId)
                ->where('campaign_id', $campaign->id)
                ->where(function ($q) use ($email, $userId, $event) {
                    if ($email) {
                        $q->orWhere('email', $email);
                    }
                    if ($userId) {
                        $q->orWhere('user_id', $userId);
                    }
                    if ($event->visitor_id) {
                        $visitorUuid = DB::table('analytics_visitors')->where('id', $event->visitor_id)->value('visitor_uuid');
                        if ($visitorUuid) {
                            $q->orWhere('visitor_uuid', $visitorUuid);
                        }
                    }
                })
                ->where('created_at', '>=', now()->subDays(7))
                ->exists();

            if ($exists) {
                continue;
            }

            $visitorUuid = null;
            if ($event->visitor_id) {
                $visitorUuid = DB::table('analytics_visitors')->where('id', $event->visitor_id)->value('visitor_uuid');
            }

            DB::table('analytics_recovery_queue')->insert([
                'campaign_id' => $campaign->id,
                'site_id' => $siteId,
                'visitor_uuid' => $visitorUuid,
                'user_id' => $userId,
                'email' => $email,
                'phone' => $phone,
                'cart_snapshot' => json_encode([
                    'revenue' => (float) ($event->revenue ?: ($props['cart_total'] ?? $props['value'] ?? 0)),
                    'currency' => $props['currency'] ?? null,
                    'properties' => $props,
                    'abandoned_at' => optional($event->occurred_at)->toIso8601String(),
                ]),
                'restore_token' => Str::random(40),
                'scheduled_at' => Carbon::parse($event->occurred_at)->addMinutes($firstDelay),
                'status' => 'pending',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $enqueued++;
        }

        return ['enqueued' => $enqueued, 'message' => "Queued {$enqueued} recovery messages."];
    }

    public function processDue(int $limit = 50): array
    {
        if (!AnalyticsSchema::hasTable('analytics_recovery_queue')) {
            return ['sent' => 0, 'failed' => 0];
        }

        $rows = DB::table('analytics_recovery_queue')
            ->where('status', 'pending')
            ->where('scheduled_at', '<=', now())
            ->orderBy('scheduled_at')
            ->limit($limit)
            ->get();

        $sent = 0;
        $failed = 0;

        foreach ($rows as $row) {
            try {
                $campaign = DB::table('analytics_recovery_campaigns')->where('id', $row->campaign_id)->first();
                $template = json_decode($campaign->template ?? '{}', true) ?: [];
                $subject = $template['subject'] ?? 'You left something in your cart';
                $body = $template['body'] ?? 'Complete your order before items sell out.';
                $restoreUrl = rtrim(config('app.url'), '/') . '/checkout?recover=' . $row->restore_token;

                if ($row->email) {
                    SendCartRecoveryMailJob::dispatch($row->email, $subject, $body, $restoreUrl, (float) ($campaign->discount_percent ?? 0));
                }

                DB::table('analytics_recovery_queue')->where('id', $row->id)->update([
                    'status' => 'sent',
                    'sent_at' => now(),
                    'updated_at' => now(),
                ]);
                $sent++;
            } catch (\Throwable $e) {
                DB::table('analytics_recovery_queue')->where('id', $row->id)->update([
                    'status' => 'failed',
                    'updated_at' => now(),
                ]);
                $failed++;
            }
        }

        return ['sent' => $sent, 'failed' => $failed];
    }

    public function sendNow(string $email, string $subject, string $body, string $restoreUrl, float $discount = 0): void
    {
        Mail::to($email)->send(new CartRecoveryMail($subject, $body, $restoreUrl, $discount));
    }
}

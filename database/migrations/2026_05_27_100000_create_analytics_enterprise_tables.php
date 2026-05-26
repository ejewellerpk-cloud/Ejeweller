<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('analytics_behavior_events')) {
            Schema::create('analytics_behavior_events', function (Blueprint $table) {
                $table->id();
                $table->foreignId('site_id')->constrained('analytics_sites')->cascadeOnDelete();
                $table->uuid('session_uuid')->index();
                $table->uuid('visitor_uuid')->nullable()->index();
                $table->string('event_type', 32)->index();
                $table->string('page_path', 512)->index();
                $table->unsignedSmallInteger('viewport_w')->default(0);
                $table->unsignedSmallInteger('viewport_h')->default(0);
                $table->string('device_type', 16)->nullable()->index();
                $table->json('payload');
                $table->timestamp('occurred_at')->index();
                $table->timestamp('created_at')->useCurrent();

                $table->index(['site_id', 'page_path', 'occurred_at'], 'analytics_behavior_site_path_idx');
            });
        }

        if (!Schema::hasTable('analytics_heatmap_buckets')) {
            Schema::create('analytics_heatmap_buckets', function (Blueprint $table) {
                $table->id();
                $table->foreignId('site_id')->constrained('analytics_sites')->cascadeOnDelete();
                $table->date('bucket_date');
                $table->string('page_path', 512);
                $table->string('heatmap_type', 16);
                $table->string('device_type', 16)->default('all');
                $table->unsignedTinyInteger('grid_x');
                $table->unsignedTinyInteger('grid_y');
                $table->unsignedInteger('count')->default(0);
                $table->timestamps();

                $table->unique(
                    ['site_id', 'bucket_date', 'page_path', 'heatmap_type', 'device_type', 'grid_x', 'grid_y'],
                    'analytics_heatmap_bucket_unq'
                );
            });
        }

        if (!Schema::hasTable('analytics_replay_recordings')) {
            Schema::create('analytics_replay_recordings', function (Blueprint $table) {
                $table->id();
                $table->foreignId('site_id')->constrained('analytics_sites')->cascadeOnDelete();
                $table->uuid('session_uuid')->index();
                $table->uuid('visitor_uuid')->nullable()->index();
                $table->unsignedBigInteger('user_id')->nullable()->index();
                $table->unsignedInteger('duration_ms')->default(0);
                $table->unsignedSmallInteger('page_count')->default(0);
                $table->string('entry_url', 2048)->nullable();
                $table->string('device_type', 16)->nullable();
                $table->string('country', 8)->nullable();
                $table->unsignedInteger('event_count')->default(0);
                $table->unsignedInteger('size_bytes')->default(0);
                $table->boolean('has_errors')->default(false);
                $table->timestamp('started_at')->nullable()->index();
                $table->timestamp('ended_at')->nullable();
                $table->timestamps();

                $table->index(['site_id', 'started_at'], 'analytics_replay_site_started_idx');
            });
        }

        if (!Schema::hasTable('analytics_replay_chunks')) {
            Schema::create('analytics_replay_chunks', function (Blueprint $table) {
                $table->id();
                $table->foreignId('recording_id')->constrained('analytics_replay_recordings')->cascadeOnDelete();
                $table->unsignedSmallInteger('sequence')->default(0);
                $table->string('storage_path', 512)->nullable();
                $table->mediumText('payload')->nullable();
                $table->unsignedInteger('size_bytes')->default(0);
                $table->timestamp('created_at')->useCurrent();

                $table->unique(['recording_id', 'sequence'], 'analytics_replay_chunk_seq_unq');
            });
        }

        if (!Schema::hasTable('analytics_ai_insights')) {
            Schema::create('analytics_ai_insights', function (Blueprint $table) {
                $table->id();
                $table->foreignId('site_id')->constrained('analytics_sites')->cascadeOnDelete();
                $table->string('insight_type', 64)->index();
                $table->string('severity', 16)->default('info');
                $table->string('title');
                $table->text('summary')->nullable();
                $table->text('recommendation')->nullable();
                $table->json('metrics')->nullable();
                $table->string('status', 16)->default('active');
                $table->timestamp('detected_at')->nullable();
                $table->timestamp('dismissed_at')->nullable();
                $table->timestamps();

                $table->index(['site_id', 'status', 'detected_at'], 'analytics_insights_site_status_idx');
            });
        }

        if (!Schema::hasTable('analytics_recovery_campaigns')) {
            Schema::create('analytics_recovery_campaigns', function (Blueprint $table) {
                $table->id();
                $table->foreignId('site_id')->constrained('analytics_sites')->cascadeOnDelete();
                $table->string('name');
                $table->string('channel', 16);
                $table->json('delays_minutes');
                $table->json('template')->nullable();
                $table->decimal('discount_percent', 5, 2)->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('analytics_recovery_queue')) {
            Schema::create('analytics_recovery_queue', function (Blueprint $table) {
                $table->id();
                $table->foreignId('campaign_id')->constrained('analytics_recovery_campaigns')->cascadeOnDelete();
                $table->foreignId('site_id')->constrained('analytics_sites')->cascadeOnDelete();
                $table->uuid('visitor_uuid')->nullable();
                $table->unsignedBigInteger('user_id')->nullable();
                $table->string('email')->nullable();
                $table->string('phone')->nullable();
                $table->json('cart_snapshot');
                $table->string('restore_token', 64)->unique();
                $table->timestamp('scheduled_at')->index();
                $table->timestamp('sent_at')->nullable();
                $table->string('status', 16)->default('pending');
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('analytics_journey_entries')) {
            Schema::create('analytics_journey_entries', function (Blueprint $table) {
                $table->id();
                $table->foreignId('site_id')->constrained('analytics_sites')->cascadeOnDelete();
                $table->uuid('visitor_uuid')->nullable()->index();
                $table->unsignedBigInteger('user_id')->nullable()->index();
                $table->string('entry_type', 32);
                $table->string('label');
                $table->json('meta')->nullable();
                $table->timestamp('occurred_at')->index();
                $table->timestamps();

                $table->index(['site_id', 'visitor_uuid', 'occurred_at'], 'analytics_journey_visitor_idx');
                $table->index(['site_id', 'user_id', 'occurred_at'], 'analytics_journey_user_idx');
            });
        }

        if (!Schema::hasTable('analytics_customer_scores')) {
            Schema::create('analytics_customer_scores', function (Blueprint $table) {
                $table->id();
                $table->foreignId('site_id')->constrained('analytics_sites')->cascadeOnDelete();
                $table->unsignedBigInteger('user_id')->nullable();
                $table->uuid('visitor_uuid')->nullable();
                $table->decimal('ltv', 14, 2)->default(0);
                $table->decimal('churn_probability', 5, 4)->nullable();
                $table->unsignedTinyInteger('rfm_recency')->nullable();
                $table->unsignedTinyInteger('rfm_frequency')->nullable();
                $table->unsignedTinyInteger('rfm_monetary')->nullable();
                $table->string('segment_slug', 32)->nullable()->index();
                $table->timestamp('calculated_at')->nullable();
                $table->timestamps();

                $table->index(['site_id', 'segment_slug'], 'analytics_scores_segment_idx');
            });
        }

        if (!Schema::hasTable('analytics_experiments')) {
            Schema::create('analytics_experiments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('site_id')->constrained('analytics_sites')->cascadeOnDelete();
                $table->string('name');
                $table->string('experiment_key', 64)->index();
                $table->string('status', 16)->default('draft');
                $table->json('variants');
                $table->unsignedTinyInteger('traffic_percent')->default(100);
                $table->timestamp('starts_at')->nullable();
                $table->timestamp('ends_at')->nullable();
                $table->timestamps();

                $table->unique(['site_id', 'experiment_key'], 'analytics_experiments_key_unq');
            });
        }

        if (!Schema::hasTable('analytics_experiment_assignments')) {
            Schema::create('analytics_experiment_assignments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('experiment_id')->constrained('analytics_experiments')->cascadeOnDelete();
                $table->uuid('visitor_uuid');
                $table->string('variant_key', 32);
                $table->timestamp('assigned_at');
                $table->timestamps();

                $table->unique(['experiment_id', 'visitor_uuid'], 'analytics_exp_assign_unq');
            });
        }

        if (!Schema::hasTable('analytics_attribution_touches')) {
            Schema::create('analytics_attribution_touches', function (Blueprint $table) {
                $table->id();
                $table->foreignId('site_id')->constrained('analytics_sites')->cascadeOnDelete();
                $table->unsignedBigInteger('order_id')->nullable()->index();
                $table->uuid('session_uuid')->nullable();
                $table->string('source', 64)->nullable();
                $table->string('medium', 64)->nullable();
                $table->string('campaign', 128)->nullable();
                $table->decimal('revenue', 14, 2)->default(0);
                $table->unsignedTinyInteger('touch_position')->default(1);
                $table->timestamp('touched_at')->nullable();
                $table->timestamps();

                $table->index(['site_id', 'touched_at'], 'analytics_attribution_site_idx');
            });
        }

        if (!Schema::hasTable('analytics_campaign_costs')) {
            Schema::create('analytics_campaign_costs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('site_id')->constrained('analytics_sites')->cascadeOnDelete();
                $table->date('cost_date');
                $table->string('source', 64);
                $table->string('campaign', 128)->nullable();
                $table->decimal('spend', 14, 2)->default(0);
                $table->string('currency', 8)->default('USD');
                $table->timestamps();

                $table->index(['site_id', 'cost_date', 'source'], 'analytics_costs_site_idx');
            });
        }

        if (!Schema::hasTable('analytics_alert_rules')) {
            Schema::create('analytics_alert_rules', function (Blueprint $table) {
                $table->id();
                $table->foreignId('site_id')->constrained('analytics_sites')->cascadeOnDelete();
                $table->string('name');
                $table->string('metric', 64);
                $table->string('operator', 8);
                $table->decimal('threshold', 14, 4);
                $table->json('channels');
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('analytics_alert_incidents')) {
            Schema::create('analytics_alert_incidents', function (Blueprint $table) {
                $table->id();
                $table->foreignId('rule_id')->constrained('analytics_alert_rules')->cascadeOnDelete();
                $table->foreignId('site_id')->constrained('analytics_sites')->cascadeOnDelete();
                $table->decimal('observed_value', 14, 4);
                $table->string('severity', 16)->default('warning');
                $table->text('message')->nullable();
                $table->timestamp('fired_at');
                $table->timestamp('acknowledged_at')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('analytics_saas_plans')) {
            Schema::create('analytics_saas_plans', function (Blueprint $table) {
                $table->id();
                $table->string('slug', 32)->unique();
                $table->string('name');
                $table->decimal('price_monthly', 10, 2)->default(0);
                $table->json('limits');
                $table->json('features');
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('analytics_saas_subscriptions')) {
            Schema::create('analytics_saas_subscriptions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('workspace_id')->constrained('analytics_workspaces')->cascadeOnDelete();
                $table->foreignId('plan_id')->constrained('analytics_saas_plans');
                $table->string('status', 16)->default('active');
                $table->string('stripe_subscription_id')->nullable();
                $table->timestamp('current_period_end')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        $tables = [
            'analytics_saas_subscriptions',
            'analytics_saas_plans',
            'analytics_alert_incidents',
            'analytics_alert_rules',
            'analytics_campaign_costs',
            'analytics_attribution_touches',
            'analytics_experiment_assignments',
            'analytics_experiments',
            'analytics_customer_scores',
            'analytics_journey_entries',
            'analytics_recovery_queue',
            'analytics_recovery_campaigns',
            'analytics_ai_insights',
            'analytics_replay_chunks',
            'analytics_replay_recordings',
            'analytics_heatmap_buckets',
            'analytics_behavior_events',
        ];

        foreach ($tables as $table) {
            Schema::dropIfExists($table);
        }
    }
};

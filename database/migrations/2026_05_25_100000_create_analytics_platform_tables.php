<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('analytics_workspaces')) {
            Schema::create('analytics_workspaces', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('slug')->unique();
                $table->foreignId('owner_id')->nullable()->constrained('users')->nullOnDelete();
                $table->json('settings')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('analytics_sites')) {
            Schema::create('analytics_sites', function (Blueprint $table) {
                $table->id();
                $table->foreignId('workspace_id')->constrained('analytics_workspaces')->cascadeOnDelete();
                $table->string('name');
                $table->string('domain')->index();
                $table->string('public_key', 64)->unique();
                $table->string('secret_key_hash', 128);
                $table->json('allowed_origins')->nullable();
                $table->string('timezone', 64)->default('UTC');
                $table->string('currency', 8)->default('USD');
                $table->json('settings')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->index(['workspace_id', 'is_active'], 'analytics_sites_ws_active_idx');
            });
        }

        if (!Schema::hasTable('analytics_site_members')) {
            Schema::create('analytics_site_members', function (Blueprint $table) {
                $table->id();
                $table->foreignId('site_id')->constrained('analytics_sites')->cascadeOnDelete();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->string('role', 32)->default('viewer');
                $table->timestamps();

                $table->unique(['site_id', 'user_id'], 'analytics_site_members_site_user_unq');
            });
        }

        if (!Schema::hasTable('analytics_visitors')) {
            Schema::create('analytics_visitors', function (Blueprint $table) {
                $table->id();
                $table->foreignId('site_id')->constrained('analytics_sites')->cascadeOnDelete();
                $table->uuid('visitor_uuid')->index();
                $table->unsignedBigInteger('user_id')->nullable()->index();
                $table->string('first_source', 64)->nullable();
                $table->string('first_medium', 64)->nullable();
                $table->string('first_campaign', 128)->nullable();
                $table->timestamp('first_seen_at')->nullable();
                $table->timestamp('last_seen_at')->nullable();
                $table->unsignedInteger('session_count')->default(0);
                $table->unsignedInteger('order_count')->default(0);
                $table->decimal('lifetime_value', 14, 2)->default(0);
                $table->timestamps();

                $table->unique(['site_id', 'visitor_uuid'], 'analytics_visitors_site_uuid_unq');
                $table->index(['site_id', 'last_seen_at'], 'analytics_visitors_site_seen_idx');
            });
        }

        if (!Schema::hasTable('analytics_sessions')) {
            Schema::create('analytics_sessions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('site_id')->constrained('analytics_sites')->cascadeOnDelete();
                $table->foreignId('visitor_id')->nullable()->constrained('analytics_visitors')->nullOnDelete();
                $table->uuid('session_uuid')->index();
                $table->unsignedBigInteger('user_id')->nullable()->index();
                $table->string('landing_page', 2048)->nullable();
                $table->string('exit_page', 2048)->nullable();
                $table->string('referrer', 2048)->nullable();
                $table->string('source', 64)->nullable()->index();
                $table->string('medium', 64)->nullable();
                $table->string('campaign', 128)->nullable();
                $table->string('content', 128)->nullable();
                $table->string('term', 128)->nullable();
                $table->unsignedSmallInteger('page_views')->default(0);
                $table->unsignedInteger('duration_seconds')->default(0);
                $table->unsignedTinyInteger('max_scroll_depth')->default(0);
                $table->boolean('is_bounce')->default(true);
                $table->boolean('is_active')->default(true);
                $table->timestamp('started_at')->nullable()->index();
                $table->timestamp('ended_at')->nullable();
                $table->timestamps();

                $table->unique(['site_id', 'session_uuid'], 'analytics_sessions_site_uuid_unq');
                $table->index(['site_id', 'started_at'], 'analytics_sessions_site_started_idx');
                $table->index(['site_id', 'is_active'], 'analytics_sessions_site_active_idx');
            });
        }

        if (!Schema::hasTable('analytics_devices')) {
            Schema::create('analytics_devices', function (Blueprint $table) {
                $table->id();
                $table->foreignId('session_id')->constrained('analytics_sessions')->cascadeOnDelete();
                $table->string('ip_hash', 64)->nullable();
                $table->string('country', 8)->nullable()->index();
                $table->string('city', 128)->nullable();
                $table->string('timezone', 64)->nullable();
                $table->string('browser', 64)->nullable();
                $table->string('browser_version', 32)->nullable();
                $table->string('os', 64)->nullable();
                $table->string('device_type', 32)->nullable()->index();
                $table->string('screen', 32)->nullable();
                $table->string('language', 16)->nullable();
                $table->string('network_type', 32)->nullable();
                $table->string('user_agent', 512)->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('analytics_events')) {
            Schema::create('analytics_events', function (Blueprint $table) {
                $table->id();
                $table->foreignId('site_id')->constrained('analytics_sites')->cascadeOnDelete();
                $table->foreignId('session_id')->nullable()->constrained('analytics_sessions')->nullOnDelete();
                $table->foreignId('visitor_id')->nullable()->constrained('analytics_visitors')->nullOnDelete();
                $table->uuid('event_uuid');
                $table->string('event_name', 128)->index();
                $table->string('event_category', 64)->default('general')->index();
                $table->string('page_url', 2048)->nullable();
                $table->string('page_title', 512)->nullable();
                $table->unsignedBigInteger('product_id')->nullable()->index();
                $table->string('product_sku', 128)->nullable();
                $table->decimal('revenue', 14, 2)->nullable();
                $table->string('currency', 8)->nullable();
                $table->unsignedBigInteger('order_id')->nullable()->index();
                $table->json('properties')->nullable();
                $table->timestamp('occurred_at')->index();
                $table->date('event_date')->index();
                $table->timestamp('ingested_at')->useCurrent();
                $table->timestamps();

                $table->unique(['site_id', 'event_uuid'], 'analytics_events_site_uuid_unq');
                $table->index(['site_id', 'event_date', 'event_name'], 'analytics_events_site_date_name_idx');
                $table->index(['site_id', 'session_id', 'occurred_at'], 'analytics_events_site_sess_time_idx');
            });
        }

        if (!Schema::hasTable('analytics_event_failures')) {
            Schema::create('analytics_event_failures', function (Blueprint $table) {
                $table->id();
                $table->foreignId('site_id')->nullable()->constrained('analytics_sites')->nullOnDelete();
                $table->json('payload');
                $table->text('error_message')->nullable();
                $table->unsignedTinyInteger('attempts')->default(0);
                $table->timestamp('failed_at')->useCurrent();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('analytics_daily_metrics')) {
            Schema::create('analytics_daily_metrics', function (Blueprint $table) {
                $table->id();
                $table->foreignId('site_id')->constrained('analytics_sites')->cascadeOnDelete();
                $table->date('metric_date')->index();
                $table->unsignedInteger('visitors')->default(0);
                $table->unsignedInteger('sessions')->default(0);
                $table->unsignedInteger('page_views')->default(0);
                $table->unsignedInteger('orders')->default(0);
                $table->decimal('revenue', 14, 2)->default(0);
                $table->unsignedInteger('bounces')->default(0);
                $table->unsignedInteger('add_to_carts')->default(0);
                $table->unsignedInteger('checkouts_started')->default(0);
                $table->unsignedInteger('checkouts_abandoned')->default(0);
                $table->json('by_source')->nullable();
                $table->json('by_device')->nullable();
                $table->timestamps();

                $table->unique(['site_id', 'metric_date'], 'analytics_daily_metrics_site_date_unq');
            });
        }

        if (!Schema::hasTable('analytics_funnel_definitions')) {
            Schema::create('analytics_funnel_definitions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('site_id')->constrained('analytics_sites')->cascadeOnDelete();
                $table->string('name');
                $table->json('steps');
                $table->boolean('is_default')->default(false);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('analytics_heatmap_points')) {
            Schema::create('analytics_heatmap_points', function (Blueprint $table) {
                $table->id();
                $table->foreignId('site_id')->constrained('analytics_sites')->cascadeOnDelete();
                $table->string('page_path', 512)->index();
                $table->string('heatmap_type', 32)->index();
                $table->unsignedSmallInteger('x')->nullable();
                $table->unsignedSmallInteger('y')->nullable();
                $table->unsignedSmallInteger('scroll_depth')->nullable();
                $table->unsignedInteger('weight')->default(1);
                $table->date('point_date')->index();
                $table->timestamps();

                $table->index(['site_id', 'page_path', 'heatmap_type', 'point_date'], 'analytics_hm_site_page_type_date_idx');
            });
        }

        if (!Schema::hasTable('analytics_replay_chunks')) {
            Schema::create('analytics_replay_chunks', function (Blueprint $table) {
                $table->id();
                $table->foreignId('site_id')->constrained('analytics_sites')->cascadeOnDelete();
                $table->uuid('session_uuid')->index();
                $table->unsignedInteger('chunk_index')->default(0);
                $table->json('events');
                $table->unsignedInteger('event_count')->default(0);
                $table->timestamp('recorded_at')->nullable();
                $table->timestamps();

                $table->index(['site_id', 'session_uuid', 'chunk_index'], 'analytics_replay_site_sess_chunk_idx');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('analytics_replay_chunks');
        Schema::dropIfExists('analytics_heatmap_points');
        Schema::dropIfExists('analytics_funnel_definitions');
        Schema::dropIfExists('analytics_daily_metrics');
        Schema::dropIfExists('analytics_event_failures');
        Schema::dropIfExists('analytics_events');
        Schema::dropIfExists('analytics_devices');
        Schema::dropIfExists('analytics_sessions');
        Schema::dropIfExists('analytics_visitors');
        Schema::dropIfExists('analytics_site_members');
        Schema::dropIfExists('analytics_sites');
        Schema::dropIfExists('analytics_workspaces');
    }
};

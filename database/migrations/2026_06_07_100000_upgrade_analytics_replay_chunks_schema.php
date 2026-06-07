<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('analytics_replay_chunks')) {
            $this->createEnterpriseReplayChunksTable();

            return;
        }

        if (Schema::hasColumn('analytics_replay_chunks', 'sequence')) {
            return;
        }

        // Legacy platform schema (site_id, chunk_index, events) — replace with enterprise schema.
        Schema::drop('analytics_replay_chunks');
        $this->createEnterpriseReplayChunksTable();
    }

    public function down(): void
    {
        if (!Schema::hasTable('analytics_replay_chunks')) {
            return;
        }

        if (!Schema::hasColumn('analytics_replay_chunks', 'sequence')) {
            return;
        }

        Schema::drop('analytics_replay_chunks');

        if (Schema::hasTable('analytics_sites')) {
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

    private function createEnterpriseReplayChunksTable(): void
    {
        if (!Schema::hasTable('analytics_replay_recordings')) {
            return;
        }

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
};

<?php

use App\Enums\FcmPlatform;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_fcm_tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('token', 512)->unique();
            $table->string('platform', 20)->default(FcmPlatform::WEB);
            $table->string('device_name')->nullable();
            $table->string('device_id', 100)->nullable();
            $table->text('user_agent')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['user_id', 'is_active']);
            $table->index(['user_id', 'platform']);
            $table->index('last_used_at');
        });

        if (!Schema::hasTable('users')) {
            return;
        }

        $now = now();

        DB::table('users')
            ->whereNotNull('web_token')
            ->where('web_token', '!=', '')
            ->orderBy('id')
            ->chunkById(200, function ($users) use ($now) {
                foreach ($users as $user) {
                    DB::table('user_fcm_tokens')->updateOrInsert(
                        ['token' => $user->web_token],
                        [
                            'user_id'      => $user->id,
                            'platform'     => FcmPlatform::WEB,
                            'device_name'  => 'Web Browser',
                            'last_used_at' => $now,
                            'is_active'    => true,
                            'created_at'   => $now,
                            'updated_at'   => $now,
                        ]
                    );
                }
            });

        DB::table('users')
            ->whereNotNull('device_token')
            ->where('device_token', '!=', '')
            ->orderBy('id')
            ->chunkById(200, function ($users) use ($now) {
                foreach ($users as $user) {
                    if ($user->device_token === $user->web_token) {
                        continue;
                    }

                    DB::table('user_fcm_tokens')->updateOrInsert(
                        ['token' => $user->device_token],
                        [
                            'user_id'      => $user->id,
                            'platform'     => FcmPlatform::ANDROID,
                            'device_name'  => 'Mobile App',
                            'last_used_at' => $now,
                            'is_active'    => true,
                            'created_at'   => $now,
                            'updated_at'   => $now,
                        ]
                    );
                }
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_fcm_tokens');
    }
};

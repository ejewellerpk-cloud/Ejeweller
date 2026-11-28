<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('product_reviews') && !Schema::hasColumn('product_reviews', 'reviewer_name')) {
            Schema::table('product_reviews', function (Blueprint $table) {
                $table->string('reviewer_name', 120)->nullable()->after('user_id');
            });
        }

        if (Schema::hasTable('product_reviews')) {
            // Drop FK if present (name can vary by MySQL/MariaDB installs).
            try {
                Schema::table('product_reviews', function (Blueprint $table) {
                    $table->dropForeign(['user_id']);
                });
            } catch (\Throwable $e) {
                // ignore
            }

            // Avoid doctrine/dbal ::change() — raw alter works on typical MySQL hosts.
            try {
                DB::statement('ALTER TABLE product_reviews MODIFY user_id BIGINT UNSIGNED NULL');
            } catch (\Throwable $e) {
                // ignore if already nullable
            }

            try {
                Schema::table('product_reviews', function (Blueprint $table) {
                    $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
                });
            } catch (\Throwable $e) {
                // ignore if FK already exists
            }
        }

        // Move names from previously generated reviewer users onto reviews, then remove those users.
        if (Schema::hasTable('users') && Schema::hasTable('product_reviews')) {
            $generatedUserIds = DB::table('users')
                ->where(function ($q) {
                    $q->where('email', 'like', 'reviewgen.%@shopperzz.internal')
                        ->orWhere('username', 'like', 'rg_%');
                })
                ->pluck('id');

            if ($generatedUserIds->isNotEmpty()) {
                $users = DB::table('users')->whereIn('id', $generatedUserIds)->get(['id', 'name']);
                foreach ($users as $user) {
                    DB::table('product_reviews')
                        ->where('user_id', $user->id)
                        ->update([
                            'reviewer_name' => $user->name,
                            'user_id' => null,
                            'updated_at' => now(),
                        ]);
                }

                if (Schema::hasTable('model_has_roles')) {
                    DB::table('model_has_roles')
                        ->where('model_type', 'App\\Models\\User')
                        ->whereIn('model_id', $generatedUserIds)
                        ->delete();
                }

                // Hard-delete fake generator accounts (and soft-deleted copies).
                DB::table('users')->whereIn('id', $generatedUserIds)->delete();
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('product_reviews') && Schema::hasColumn('product_reviews', 'reviewer_name')) {
            Schema::table('product_reviews', function (Blueprint $table) {
                $table->dropColumn('reviewer_name');
            });
        }
    }
};

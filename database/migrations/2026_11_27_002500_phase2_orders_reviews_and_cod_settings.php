<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Dipokhalder\Settings\Facades\Settings;
use App\Enums\Ask;
use App\Enums\ReviewStatus;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (!Schema::hasColumn('orders', 'idempotency_key')) {
                $table->string('idempotency_key', 64)->nullable()->after('order_serial_no');
                $table->unique('idempotency_key');
            }
        });

        Schema::table('product_reviews', function (Blueprint $table) {
            if (!Schema::hasColumn('product_reviews', 'status')) {
                // Default approved so legacy rows stay public; customer submits set PENDING in app code.
                $table->tinyInteger('status')->default(ReviewStatus::APPROVED)->after('review');
            }
            if (!Schema::hasColumn('product_reviews', 'is_verified_purchase')) {
                $table->tinyInteger('is_verified_purchase')->default(Ask::NO)->after('status');
            }
        });

        try {
            Settings::group('site')->set([
                'site_cod_min_order' => 0,
                'site_cod_max_order' => 0,
                'site_cod_fee'       => 0,
            ]);
        } catch (\Throwable $e) {
            // Settings table may not exist during fresh installs before seeders.
        }
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (Schema::hasColumn('orders', 'idempotency_key')) {
                $table->dropUnique(['idempotency_key']);
                $table->dropColumn('idempotency_key');
            }
        });

        Schema::table('product_reviews', function (Blueprint $table) {
            if (Schema::hasColumn('product_reviews', 'is_verified_purchase')) {
                $table->dropColumn('is_verified_purchase');
            }
            if (Schema::hasColumn('product_reviews', 'status')) {
                $table->dropColumn('status');
            }
        });
    }
};

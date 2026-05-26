<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('postex_tracking_number', 64)->nullable()->after('note');
            $table->string('postex_status', 64)->nullable()->after('postex_tracking_number');
            $table->timestamp('postex_booked_at')->nullable()->after('postex_status');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['postex_tracking_number', 'postex_status', 'postex_booked_at']);
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $table = config('settings.repositories.database.table', 'settings');
        $exists = DB::table($table)
            ->where('group', 'site')
            ->where('key', 'site_abandoned_cart_reminder')
            ->exists();

        if (!$exists) {
            DB::table($table)->insert([
                'group'      => 'site',
                'key'        => 'site_abandoned_cart_reminder',
                'payload'    => json_encode(5),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        $table = config('settings.repositories.database.table', 'settings');
        DB::table($table)
            ->where('group', 'site')
            ->where('key', 'site_abandoned_cart_reminder')
            ->delete();
    }
};

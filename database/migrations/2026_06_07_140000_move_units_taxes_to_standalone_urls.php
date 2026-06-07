<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $map = [
            'settings/units' => 'units',
            'settings/taxes' => 'taxes',
        ];

        foreach ($map as $from => $to) {
            DB::table('menus')
                ->where('url', $from)
                ->update([
                    'url'        => $to,
                    'updated_at' => now(),
                ]);
        }
    }

    public function down(): void
    {
        $map = [
            'units' => 'settings/units',
            'taxes' => 'settings/taxes',
        ];

        foreach ($map as $from => $to) {
            DB::table('menus')
                ->where('url', $from)
                ->update([
                    'url'        => $to,
                    'updated_at' => now(),
                ]);
        }
    }
};

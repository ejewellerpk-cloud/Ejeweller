<?php

use App\Enums\MenuType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $exists = DB::table('menus')->where('url', 'postex-shipments')->exists();
        if ($exists) {
            return;
        }

        $onlineOrders = DB::table('menus')->where('url', 'online-orders')->first();
        $priority = $onlineOrders ? ((int) $onlineOrders->priority + 1) : 31;

        DB::table('menus')->insert([
            'name'       => 'PostEx Shipments',
            'language'   => 'postex_shipments',
            'url'        => 'postex-shipments',
            'icon'       => 'lab lab-line-truck-check',
            'priority'   => $priority,
            'status'     => 1,
            'parent'     => 0,
            'type'       => MenuType::BACKEND,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        DB::table('menus')->where('url', 'postex-shipments')->delete();
    }
};

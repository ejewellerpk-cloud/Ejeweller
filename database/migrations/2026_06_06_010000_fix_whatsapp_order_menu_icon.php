<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('menus')
            ->where('url', 'whatsapp-order')
            ->update([
                'icon'       => 'lab lab-whatsapp',
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        //
    }
};

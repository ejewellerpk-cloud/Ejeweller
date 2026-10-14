<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Analytics has its own dashboard — remove in-admin sidebar entry if present
        DB::table('menus')->where('url', 'intelligence')->delete();
    }

    public function down(): void
    {
        // Intentionally empty — do not re-add sidebar menu
    }
};

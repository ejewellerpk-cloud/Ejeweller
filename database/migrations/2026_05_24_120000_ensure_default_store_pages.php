<?php

use App\Services\DefaultPagesService;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        app(DefaultPagesService::class)->ensure();
    }

    public function down(): void
    {
        // Merchants may edit pages; do not delete on rollback.
    }
};

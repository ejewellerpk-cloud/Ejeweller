<?php

use App\Enums\MenuType;
use App\Enums\Role as EnumRole;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('marketing_campaigns')) {
            Schema::create('marketing_campaigns', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('channel', 32)->default('meta');
                $table->string('objective', 32)->default('conversions');
                $table->string('status', 16)->default('draft');
                $table->string('utm_source', 64)->default('facebook');
                $table->string('utm_medium', 64)->default('cpc');
                $table->string('utm_campaign', 128);
                $table->string('utm_content', 128)->nullable();
                $table->string('utm_term', 128)->nullable();
                $table->string('landing_path', 255)->default('/');
                $table->decimal('daily_budget', 14, 2)->nullable();
                $table->decimal('total_budget', 14, 2)->nullable();
                $table->string('currency', 8)->default('PKR');
                $table->date('starts_at')->nullable();
                $table->date('ends_at')->nullable();
                $table->string('external_campaign_id', 64)->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->index(['channel', 'status']);
                $table->unique('utm_campaign');
            });
        }

        if (!Schema::hasTable('marketing_campaign_spends')) {
            Schema::create('marketing_campaign_spends', function (Blueprint $table) {
                $table->id();
                $table->foreignId('marketing_campaign_id')->constrained('marketing_campaigns')->cascadeOnDelete();
                $table->date('spend_date');
                $table->decimal('spend', 14, 2)->default(0);
                $table->string('currency', 8)->default('PKR');
                $table->unsignedInteger('impressions')->nullable();
                $table->unsignedInteger('clicks')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->index(['marketing_campaign_id', 'spend_date']);
            });
        }

        $permission = Permission::firstOrCreate(
            ['name' => 'campaigns', 'guard_name' => 'sanctum'],
            ['title' => 'Campaigns', 'url' => 'campaigns']
        );

        Role::find(EnumRole::ADMIN)?->givePermissionTo($permission);
        Role::find(EnumRole::MANAGER)?->givePermissionTo($permission);

        $promo = DB::table('menus')->where('language', 'promo')->where('parent', 0)->first();
        if ($promo && !DB::table('menus')->where('url', 'campaigns')->exists()) {
            DB::table('menus')->insert([
                'name'       => 'Campaigns',
                'language'   => 'campaigns',
                'url'        => 'campaigns',
                'icon'       => 'lab lab-line-promotion',
                'priority'   => 90,
                'status'     => 1,
                'parent'     => $promo->id,
                'type'       => MenuType::BACKEND,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        try {
            Artisan::call('permission:cache-reset');
        } catch (\Throwable $e) {
            // best-effort
        }
    }

    public function down(): void
    {
        DB::table('menus')->where('url', 'campaigns')->delete();

        $permission = Permission::where('name', 'campaigns')->where('guard_name', 'sanctum')->first();
        if ($permission) {
            DB::table('role_has_permissions')->where('permission_id', $permission->id)->delete();
            $permission->delete();
        }

        Schema::dropIfExists('marketing_campaign_spends');
        Schema::dropIfExists('marketing_campaigns');

        try {
            Artisan::call('permission:cache-reset');
        } catch (\Throwable $e) {
            //
        }
    }
};

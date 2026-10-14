<?php

namespace Database\Seeders;

use App\Libraries\AppLibrary;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class PermissionTableSeederVersionFour extends Seeder
{
    /**
     * Seed the Analytics (intelligence) permission for upgrades that run seeders.
     */
    public function run(): void
    {
        if (Permission::where('name', 'intelligence')->exists()) {
            return;
        }

        $permissions = AppLibrary::associativeToNumericArrayBuilder([
            [
                'title'      => 'Analytics',
                'name'       => 'intelligence',
                'guard_name' => 'sanctum',
                'url'        => 'intelligence',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        Permission::insert($permissions);

        // Per-administrator only — do not attach to Admin role
    }
}

<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Only run if permissions table exists (Shield is installed)
        if (! Schema::hasTable('permissions')) {
            return;
        }

        // Create code coverage permission
        $permission = Permission::firstOrCreate([
            'name' => 'view_code_coverage',
            'guard_name' => 'web',
        ]);

        // Assign to super_admin role if it exists
        $superAdmin = Role::where('name', 'super_admin')->first();
        if ($superAdmin && ! $superAdmin->hasPermissionTo('view_code_coverage')) {
            $superAdmin->givePermissionTo('view_code_coverage');
        }

        // Assign to admin role if it exists
        $admin = Role::where('name', 'admin')->first();
        if ($admin && ! $admin->hasPermissionTo('view_code_coverage')) {
            $admin->givePermissionTo('view_code_coverage');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Only run if permissions table exists
        if (! Schema::hasTable('permissions')) {
            return;
        }

        Permission::where('name', 'view_code_coverage')->delete();
    }
};

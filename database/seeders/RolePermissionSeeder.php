<?php

namespace Database\Seeders;

use App\Enums\RolePermissionEnum;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Creating permissions...');

        foreach (RolePermissionEnum::PERMISSIONS as $permission) {
            Permission::updateOrCreate([
                'name' => $permission->value,
            ], [
                'guard_name' => 'sanctum',
            ]);
        }

        $this->command->info('Permissions created successfully!');

        $this->command->info('Creating super admin role...');

        Role::updateOrCreate([
            'name' => RolePermissionEnum::SUPER_ADMIN->value,
        ], [
            'guard_name' => 'sanctum',
        ]);

        $this->command->info('Super admin role created successfully!');
    }
}

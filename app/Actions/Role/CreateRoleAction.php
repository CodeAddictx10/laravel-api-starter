<?php

declare(strict_types=1);

namespace App\Actions\Role;

use App\Dtos\CreateRoleDto;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

final class CreateRoleAction
{
    public function execute(CreateRoleDto $dto): Role
    {
        return DB::transaction(function () use ($dto) {
            $role = Role::create(['name' => $dto->name]);

            $role->givePermissionTo($dto->permissions);

            return $role->load('permissions');
        });
    }
}

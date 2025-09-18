<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\Role\CreateRoleAction;
use App\Dtos\CreateRoleDto;
use App\Enums\RolePermissionEnum;
use App\Http\Resources\RoleResource;
use App\Swagger\Attributes\Delete;
use App\Swagger\Attributes\Get;
use App\Swagger\Attributes\Post;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\QueryBuilder\QueryBuilder;

final class RolePermissionController
// implements HasMiddleware
{
    // public static function middleware(): array
    // {
    //     return [
    //         'permission:'.RolePermissionEnum::CREATE_USER->value,
    //     ];
    // }

    #[Get(
        path: '/v1/roles',
        summary: 'Get roles',
        tags: 'Roles',
        responses: [
            ['statusCode' => 200, 'description' => 'OK', 'modelSchema' => 'RoleResource', 'isArray' => true],
        ]
    )]
    public function index()
    {
        $roles = QueryBuilder::for(Role::class)
            ->allowedIncludes(['permissions'])
            ->where('name', '!=', RolePermissionEnum::SUPER_ADMIN->value)
            ->get(['id', 'name']);

        return response()->json(['data' => [
            'roles' => RoleResource::collection($roles),
            'permissions' => Permission::get(['id', 'name']),
        ]]);
    }

    #[Post(
        path: '/v1/roles',
        summary: 'Create a new role',
        tags: 'Roles',
        responses: [
            ['statusCode' => 201, 'description' => 'Role created successfully', 'modelSchema' => 'RoleResource'],
        ],
        requestBody: 'CreateRoleDto',
    )]
    public function store(CreateRoleDto $dto, CreateRoleAction $action)
    {
        $role = $action->execute($dto);

        return response()->json(['data' => new RoleResource($role)], 201);
    }

    #[Delete(
        path: '/v1/roles/{id}',
        summary: 'Delete a role',
        tags: 'Roles',
        parameters: [
            ['path' => 'id:integer'],
        ],
    )]
    public function destroy(Role $role)
    {
        DB::transaction(function () use ($role) {
            $role->permissions()->detach();

            $role->delete();
        });

        return response()->json(['message' => 'Role deleted successfully'], 204);
    }
}

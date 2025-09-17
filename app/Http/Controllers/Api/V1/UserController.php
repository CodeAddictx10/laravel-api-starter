<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Models\User;
use App\Swagger\Attributes\Delete;
use App\Swagger\Attributes\Get;
use App\Swagger\Attributes\Patch;
use App\Swagger\Attributes\Post;
use App\Swagger\Attributes\Put;
use Illuminate\Http\Request;

final class UserController
{
    #[Get(
        path: '/v1/users',
        summary: 'Get all users',
        tags: 'Users',
        responses: [['statusCode' => 200, 'description' => 'OK', 'modelSchema' => 'User', 'isArray' => true]]
    )]
    public function index()
    {
        return response()->json([
            'success' => true,
        ]);
    }

    #[Post(
        path: '/v1/users',
        summary: 'Create a new user',
        tags: 'Users',
        responses: [
            ['statusCode' => 201, 'description' => 'User created successfully', 'modelSchema' => 'User'],
            ['statusCode' => 422, 'description' => 'Validation failed'],
        ],
    )]
    public function store(Request $request)
    {
        return response()->json([
            'success' => true,
            'message' => 'User created successfully',
        ], 201);
    }

    #[Get(
        path: '/v1/users/{id}',
        summary: 'Get user by ID',
        tags: 'Users',
        responses: [
            ['statusCode' => 200, 'description' => 'User found', 'modelSchema' => 'User'],
            ['statusCode' => 404, 'description' => 'User not found'],
        ]
    )]
    public function show(User $user)
    {
        return response()->json([
            'success' => true,
            'data' => $user,
        ]);
    }

    #[Put(
        path: '/v1/users/{id}',
        summary: 'Update user completely',
        tags: 'Users',
        responses: [
            ['statusCode' => 200, 'description' => 'User updated successfully', 'modelSchema' => 'User'],
            ['statusCode' => 404, 'description' => 'User not found'],
            ['statusCode' => 422, 'description' => 'Validation failed'],
        ],
    )]
    public function update(Request $request, User $user)
    {
        return response()->json([
            'success' => true,
            'message' => 'User updated successfully',
            'data' => $user,
        ]);
    }

    #[Patch(
        path: '/v1/users/{id}',
        summary: 'Partially update user',
        tags: 'Users',
        responses: [
            ['statusCode' => 200, 'description' => 'User updated successfully', 'modelSchema' => 'User'],
            ['statusCode' => 404, 'description' => 'User not found'],
            ['statusCode' => 422, 'description' => 'Validation failed'],
        ],
    )]
    public function patch(Request $request, User $user)
    {
        return response()->json([
            'success' => true,
            'message' => 'User updated successfully',
            'data' => $user,
        ]);
    }

    #[Delete(
        path: '/v1/users/{id}',
        summary: 'Delete user',
        tags: 'Users',
        responses: [
            ['statusCode' => 200, 'description' => 'User deleted successfully'],
            ['statusCode' => 404, 'description' => 'User not found'],
        ]
    )]
    public function destroy(User $user)
    {
        return response()->json([
            'success' => true,
            'message' => 'User deleted successfully',
        ]);
    }
}

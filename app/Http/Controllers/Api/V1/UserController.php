<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Models\User;
use App\Swagger\Attributes\Get;
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

    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(User $user)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, User $user)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $user)
    {
        //
    }
}

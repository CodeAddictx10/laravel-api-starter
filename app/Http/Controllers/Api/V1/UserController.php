<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Dtos\CreateUserDto;
use App\Dtos\UpdateUserDto;
use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\Rule;
use Spatie\QueryBuilder\QueryBuilder;

/**
 * @group User management
 *
 * APIs for managing users. Users can be filtered, sorted, and their relationships can be included.
 */
final class UserController extends Controller
{
    /**
     * Get Users
     *
     * Get a paginated list of users with optional filtering, sorting, and relationship inclusion.
     */
    public function index(): AnonymousResourceCollection
    {
        $users = QueryBuilder::for(User::class)
            ->allowedFilters(['name', 'email', 'created_at'])
            ->allowedSorts(['name', 'email', 'created_at', 'id'])
            ->allowedIncludes(['roles'])
            ->allowedFields(['id', 'name', 'email', 'created_at', 'updated_at'])
            ->defaultSort('-created_at')
            ->paginate(request()->integer('per_page', 15));

        return UserResource::collection($users);
    }

    /**
     * Store a newly created resource in storage.
     *
     * Create a new user with validated data.
     */
    public function store(CreateUserDto $dto): JsonResponse
    {
        $user = User::create($dto->toArray());

        return response()->json(
            ['data' => new UserResource($user)],
            201
        );
    }

    /**
     * Display the specified resource.
     *
     * Get a single user by ID.
     */
    public function show(User $user): JsonResponse
    {
        return response()->json([
            'data' => new UserResource($user),
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * Update an existing user with validated data. All fields are optional.
     */
    public function update(UpdateUserDto $dto, User $user): JsonResponse
    {
        $validated = $dto->toArray();

        // Additional validation for email uniqueness if email is being updated
        if (isset($validated['email']) && $validated['email'] !== $user->email) {
            \Illuminate\Support\Facades\Validator::make(
                ['email' => $validated['email']],
                ['email' => Rule::unique('users', 'email')->ignore($user->id)]
            )->validate();
        }

        // Filter out password if not provided
        if (empty($validated['password'])) {
            unset($validated['password']);
        }

        $user->update(array_filter($validated));

        return response()->json([
            'data' => new UserResource($user->fresh()),
        ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * Delete a user by ID.
     */
    public function destroy(User $user): JsonResponse
    {
        $user->delete();

        return response()->json(null, 204);
    }
}

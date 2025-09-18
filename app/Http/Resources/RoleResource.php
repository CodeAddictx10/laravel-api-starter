<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Swagger\Attributes\ResourceSchema;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

#[ResourceSchema(
    schemaName: 'RoleResource',
    resourceClass: self::class
)]
final class RoleResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'permissions' => $this->whenLoaded('permissions', fn () => $this->permissions->select('id', 'name')),
        ];
    }

    public static function schema(): array
    {
        return [
            'id' => ['type' => 'integer', 'example' => 1],
            'name' => ['type' => 'string', 'example' => 'Admin'],
            'permissions' => ['type' => 'object', 'properties' => ['id' => ['type' => 'integer', 'example' => 1],'name' => ['type' => 'string', 'example' => 'Create User'],
            ]],
        ];
    }
}

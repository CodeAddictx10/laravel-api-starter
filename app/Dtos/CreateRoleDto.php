<?php

declare(strict_types=1);

namespace App\Dtos;

use App\Swagger\Attributes\Schema;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use WendellAdriel\ValidatedDTO\ValidatedDTO;

#[Schema(
    schemaName: 'CreateRoleDto',
    dtoClass: self::class,
    examples: [
        'name' => 'admin',
        'permissions' => [1, 2, 3],
    ]
)]
final class CreateRoleDto extends ValidatedDTO
{
    public string $name;
    public array $permissions;

    public function failedValidation(): void
    {
        throw new BadRequestHttpException($this->validator->errors()->first());
    }

    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:50', 'min:3'],
            'permissions' => ['required', 'array'],
            'permissions.*' => ['required', 'integer'],
        ];
    }

    protected function defaults(): array
    {
        return [];
    }

    protected function casts(): array
    {
        return [];
    }
}

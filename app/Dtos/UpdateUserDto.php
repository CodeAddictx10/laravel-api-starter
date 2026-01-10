<?php

declare(strict_types=1);

namespace App\Dtos;

use WendellAdriel\ValidatedDTO\ValidatedDTO;

/**
 * DTO for updating an existing user.
 *
 * This DTO validates user update data. All fields are optional.
 */
final class UpdateUserDto extends ValidatedDTO
{
    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'email.unique' => 'The email has already been taken.',
            'password.min' => 'The password must be at least 8 characters.',
            'password.confirmed' => 'The password confirmation does not match.',
        ];
    }

    /**
     * Get the validation rules for updating a user.
     *
     * @return array<string, array<int, string>>
     */
    protected function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'email' => ['sometimes', 'required', 'string', 'email', 'max:255'],
            'password' => ['sometimes', 'nullable', 'string', 'min:8', 'confirmed'],
        ];
    }

    /**
     * Defines the default values for the properties of the DTO.
     *
     * @return array<string, mixed>
     */
    protected function defaults(): array
    {
        return [];
    }

    /**
     * Defines the type casting for the properties of the DTO.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [];
    }
}

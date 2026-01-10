<?php

declare(strict_types=1);

namespace App\Dtos;

use WendellAdriel\ValidatedDTO\ValidatedDTO;

/**
 * DTO for creating a new user.
 *
 * This DTO validates user creation data including name, email, and password.
 */
final class CreateUserDto extends ValidatedDTO
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
     * Get the validation rules for creating a user.
     *
     * @return array<string, array<int, string>>
     */
    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
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

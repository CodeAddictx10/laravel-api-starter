<?php

declare(strict_types=1);

namespace App\Swagger\Attributes;

use Attribute;
use Illuminate\Validation\Rules\Password;
use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema as OASchema;
use ReflectionClass;

#[Attribute(Attribute::TARGET_CLASS)]
final class Schema extends OASchema
{
    public function __construct(string $schemaName, string $dtoClass, array $examples = [])
    {
        $rules = [];

        if (class_exists($dtoClass)) {
            $reflection = new ReflectionClass($dtoClass);

            if ($reflection->hasMethod('rules')) {
                $instance = $reflection->newInstanceWithoutConstructor();
                $method = $reflection->getMethod('rules');
                $method->setAccessible(true);
                $rules = $method->invoke($instance);
            }
        }

        $required = [];
        $properties = [];

        foreach ($rules as $field => $fieldRules) {
            $fieldRules = is_array($fieldRules) ? $fieldRules : explode('|', $fieldRules);

            $propertyData = [
                'property' => $field,
                'type' => 'string',
            ];

            foreach ($fieldRules as $rule) {
                $this->processRule($rule, $propertyData, $required, $field);
            }

            if (isset($examples[$field])) {
                $propertyData['example'] = $examples[$field];
            }

            $properties[] = new Property(...$propertyData);
        }

        parent::__construct(
            schema: $schemaName,
            type: 'object',
            required: $required,
            properties: $properties,
            example: $examples
        );
    }

    private function processRule($rule, array &$propertyData, array &$required, string $field): void
    {
        if ($rule === 'required') {
            $required[] = $field;
        }

        if (is_string($rule)) {
            $this->processStringRule($rule, $propertyData);
        } elseif ($rule instanceof Password) {
            $this->processPasswordRule($rule, $propertyData);
        }
    }

    private function processStringRule(string $rule, array &$propertyData): void
    {
        switch ($rule) {
            case 'email':
                $propertyData['format'] = 'email';
                break;
            case 'integer':
            case 'numeric':
                $propertyData['type'] = 'integer';
                break;
            case 'boolean':
                $propertyData['type'] = 'boolean';
                break;
        }

        if (str_contains($rule, ':')) {
            [$name, $value] = explode(':', $rule, 2);

            if ($name === 'min') {
                $propertyData['minLength'] = (int) $value;
            } elseif ($name === 'max') {
                $propertyData['maxLength'] = (int) $value;
            }
        }
    }

    private function processPasswordRule(Password $rule, array &$propertyData): void
    {
        $propertyData['format'] = 'password';

        $ref = new ReflectionClass($rule);
        if ($ref->hasProperty('min')) {
            $prop = $ref->getProperty('min');
            $prop->setAccessible(true);
            $propertyData['minLength'] = $prop->getValue($rule) ?? 8;
        }
    }
}

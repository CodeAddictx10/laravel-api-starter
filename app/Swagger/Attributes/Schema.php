<?php

declare(strict_types=1);

namespace App\Swagger\Attributes;

use Attribute;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema as OASchema;
use ReflectionClass;

#[Attribute(Attribute::TARGET_CLASS)]
final class Schema extends OASchema
{
    public function __construct(string $schemaName, string $ruleClass, array $examples = [])
    {
        $rules = [];

        if (class_exists($ruleClass)) {
            $reflection = new ReflectionClass($ruleClass);

            if ($reflection->hasMethod('rules')) {
                $instance = $reflection->newInstanceWithoutConstructor();
                $method = $reflection->getMethod('rules');
                $method->setAccessible(true);
                $rules = $method->invoke($instance);
            }
        }

        $required = [];
        $nested = []; // To accumulate nested properties grouped by parent property
        $parentFields = []; // Track which fields are parents of nested properties

        // First pass: identify all parent fields
        foreach ($rules as $field => $fieldRules) {
            if (str_contains($field, '.')) {
                $parent = explode('.', $field, 2)[0];
                $parentFields[$parent] = true;
            }
        }

        // Second pass: process all rules
        foreach ($rules as $field => $fieldRules) {
            $fieldRules = is_array($fieldRules) ? $fieldRules : explode('|', $fieldRules);

            if (str_contains($field, '.')) {
                // Split to parent and child
                [$parent, $child] = explode('.', $field, 2);

                // Init parent container if not exists
                if (!isset($nested[$parent])) {
                    $nested[$parent] = [
                        'property' => $parent,
                        'type' => 'object',
                        'properties' => [],
                    ];
                }

                // Build child property data
                $childData = [
                    'property' => $child,
                    'type' => 'string', // default type
                ];

                $childRequired = [];
                foreach ($fieldRules as $rule) {
                    $this->processRule($rule, $childData, $childRequired, $child);
                }

                $nested[$parent]['properties'][] = new Property(...$childData);

                continue; // skip adding this as a top-level property
            }

            // Skip this field if it's a parent of nested properties
            if (isset($parentFields[$field])) {
                continue;
            }

            // Top-level property (not nested)
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

            $topLevelProperties[] = new Property(...$propertyData);
        }

        // Initialize topLevelProperties if not set
        if (!isset($topLevelProperties)) {
            $topLevelProperties = [];
        }

        // Combine top-level and nested properties into one array
        $properties = array_merge(
            $topLevelProperties,
            array_map(
                fn ($nestedProperty) => new Property(
                    property: $nestedProperty['property'],
                    type: $nestedProperty['type'],
                    properties: $nestedProperty['properties']
                ),
                $nested
            )
        );

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

        if ($rule === 'sometimes') {
            $propertyData['description'] = ($propertyData['description'] ?? '') . 'Conditionally required.';
        }

        if ($rule === 'nullable') {
            $propertyData['nullable'] = true;
        }

        if (is_string($rule)) {
            $this->processStringRule($rule, $propertyData);
        } elseif ($rule instanceof Password) {
            $this->processPasswordRule($rule, $propertyData);
        } elseif ($rule instanceof Rule) {
            $propertyData['type'] = 'string';
            $propertyData['description'] = ($propertyData['description'] ?? '') . ' (Rule constraint)';
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
            case 'url':
                $propertyData['format'] = 'url';
                break;
            case 'ip':
                $propertyData['format'] = 'ip';
                break;
            case 'string':
                $propertyData['type'] = 'string';
                break;
            case 'array':
                $propertyData['type'] = 'object';
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

<?php

declare(strict_types=1);

namespace App\Swagger\Attributes;

use Attribute;
use OpenApi\Attributes\Items;
use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema as OASchema;

#[Attribute(Attribute::TARGET_CLASS)]
final class ResourceSchema extends OASchema
{
    public function __construct(
        string $schemaName,
        string $resourceClass
    ) {
        $properties = [];

        if (method_exists($resourceClass, 'schema')) {
            $fields = $resourceClass::schema();

            foreach ($fields as $field => $meta) {
                $type = is_array($meta) ? ($meta['type'] ?? 'string') : $meta;
                $example = $meta['example'] ?? null;
                $enum = $meta['enum'] ?? null;

                // 1. Array of scalars, e.g. string[]
                if (str_ends_with($type, '[]') && ! preg_match('/(Schema|Resource)\[\]$/', $type)) {
                    $base = rtrim($type, '[]');
                    $properties[] = new Property(
                        property: $field,
                        type: 'array',
                        items: new Items(type: $base),
                        example: $example,
                        enum: $enum
                    );
                }

                // 2. Array of schema refs, e.g. OrganizationSchema[]
                elseif (str_ends_with($type, '[]')) {
                    $ref = '#/components/schemas/'.rtrim($type, '[]');
                    $properties[] = new Property(
                        property: $field,
                        type: 'array',
                        items: new Items(ref: $ref),
                        example: $example
                    );
                }

                // 3. Schema or Resource reference
                elseif (preg_match('/^[A-Z]\w+(Schema|Resource)$/', $type)) {
                    $ref = "#/components/schemas/{$type}";
                    $properties[] = new Property(
                        property: $field,
                        ref: $ref,
                        example: $example
                    );
                }

                // 4. Nested object with properties
                elseif ($type === 'object' && isset($meta['properties'])) {
                    $nestedProperties = [];
                    $nestedExample = [];

                    foreach ($meta['properties'] as $nestedField => $nestedMeta) {
                        $nestedType = $nestedMeta['type'] ?? 'string';
                        $nestedExample[$nestedField] = $nestedMeta['example'] ?? null;

                        $nestedProperties[] = new Property(
                            property: $nestedField,
                            type: $nestedType,
                            example: $nestedMeta['example'] ?? null
                        );
                    }

                    // If this field is an array of objects
                    if (isset($meta['isArray']) && $meta['isArray'] === true) {
                        $properties[] = new Property(
                            property: $field,
                            type: 'array',
                            items: new Items(
                                type: 'object',
                                properties: $nestedProperties,
                                example: [$nestedExample]
                            )
                        );
                    } else {
                        $properties[] = new Property(
                            property: $field,
                            type: 'object',
                            properties: $nestedProperties,
                            example: $nestedExample
                        );
                    }
                }

                // 5. Scalar with format
                elseif (str_contains($type, ':')) {
                    [$base, $format] = explode(':', $type, 2);
                    $properties[] = new Property(
                        property: $field,
                        type: $base,
                        format: $format,
                        example: $example,
                        enum: $enum
                    );
                }

                // 6. Plain scalar
                else {
                    $properties[] = new Property(
                        property: $field,
                        type: $type,
                        example: $example,
                        enum: $enum
                    );
                }
            }
        }

        parent::__construct(
            schema: $schemaName,
            type: 'object',
            properties: $properties
        );
    }
}

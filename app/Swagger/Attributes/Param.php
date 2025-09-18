<?php

declare(strict_types=1);

namespace App\Swagger\Attributes;

use InvalidArgumentException;
use OpenApi\Attributes\Parameter;
use OpenApi\Attributes\Schema;

final class Param
{
    public static function build(string $path, array $definitions): array
    {
        $expanded = [];

        foreach ($definitions as $def) {
            // Verbose form already expanded
            if (isset($def['type'], $def['name'])) {
                $expanded[] = $def;

                continue;
            }

            // Shortcut form
            if (is_array($def) && count($def) === 1 && is_string(reset($def))) {
                $expanded[] = self::expandShortcut($def);

                continue;
            }

            throw new InvalidArgumentException('Invalid parameter definition: '.json_encode($def));
        }

        $params = [];
        foreach ($expanded as $def) {
            $params[] = self::fromDefinition($def);
        }

        // Auto detect {placeholders} in path if not already declared
        foreach (self::extractPathParams($path) as $paramName) {
            if (collect($params)->contains(fn ($p) => $p->name === $paramName && $p->in === 'path')) {
                continue;
            }

            $params[] = new Parameter(
                name: $paramName,
                in: 'path',
                required: true,
                description: ucfirst($paramName).' parameter',
                schema: new Schema(type: 'string')
            );
        }

        return $params;
    }

    private static function expandShortcut(array $shortcut): array
    {
        $type = key($shortcut);
        $value = $shortcut[$type];

        // query shortcut
        if ($type === 'query') {
            [$name, $rest] = array_pad(explode(':', $value, 2), 2, null);
            [$schema, $default] = array_pad(explode('=', $rest ?? ''), 2, null);

            return [
                'type' => 'query',
                'name' => $name,
                'schema' => $schema ?: 'string',
                'default' => $default !== null ? (is_numeric($default) ? (int) $default : $default) : null,
            ];
        }

        // filter shortcut: filter[field]:val1,val2
        if ($type === 'filter') {
            [$name, $enums] = array_pad(explode(':', $value, 2), 2, null);

            return [
                'type' => 'query',
                'name' => "filter[$name]",
                'enum' => $enums ? explode(',', $enums) : null,
            ];
        }

        // path shortcut
        if ($type === 'path') {
            [$name, $schema] = array_pad(explode(':', $value, 2), 2, 'string');

            return [
                'type' => 'path',
                'name' => $name,
                'schema' => $schema,
            ];
        }

        // include shortcut
        if ($type === 'include') {
            $relations = explode(',', $value);

            return [
                'type' => 'query',
                'name' => 'include',
                'schema' => 'string',
                'description' => 'Comma-separated relations to include. Allowed: '.implode(', ', $relations),
            ];
        }

        // sort shortcut (Laravel style: +field, -field)
        if ($type === 'sort') {
            $fields = array_map('trim', explode(',', $value));
            $examples = [];
            foreach ($fields as $field) {
                $examples[] = $field;
                $examples[] = "-$field";
                $examples[] = "+$field";
            }

            return [
                'type' => 'query',
                'name' => 'sort',
                'schema' => 'string',
                'description' => 'Sort by field (prefix with - for desc, + for asc). Allowed: '.implode(', ', $fields),
                'example' => $examples,
            ];
        }

        throw new InvalidArgumentException("Unknown shortcut type [$type]");
    }

    private static function fromDefinition(array $def): Parameter
    {
        $in = $def['type'] === 'path' ? 'path' : 'query';
        $schemaType = $def['schema'] ?? ($in === 'path' ? 'string' : 'string');

        if ($in === 'path' && ! in_array($schemaType, ['string', 'integer'], true)) {
            throw new InvalidArgumentException(
                "Path parameter [{$def['name']}] must be 'string' or 'integer', got '{$schemaType}'"
            );
        }

        return new Parameter(
            name: $def['name'],
            in: $in,
            required: $in === 'path' ? true : ($def['required'] ?? false),
            description: $def['description'] ?? null,
            schema: new Schema(
                type: $schemaType,
                enum: $def['enum'] ?? null,
                default: $in === 'path' ? null : ($def['default'] ?? null),
                minimum: $def['minimum'] ?? null,
                maximum: $def['maximum'] ?? null,
                example: $def['examples'] ?? null
            )
        );
    }

    private static function extractPathParams(string $path): array
    {
        preg_match_all('/\{(\w+)\}/', $path, $matches);

        return $matches[1] ?? [];
    }
}

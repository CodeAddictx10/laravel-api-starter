<?php

declare(strict_types=1);

namespace App\Swagger\Attributes;

use InvalidArgumentException;
use OpenApi\Attributes\Items;
use OpenApi\Attributes\JsonContent;
use OpenApi\Attributes\Property;
use OpenApi\Attributes\Response as OAResponse;

final class Response
{
    /** @var OAResponse[] */
    public array $responses = [];

    public function __construct(array $definitions = [])
    {
        $defaultDescriptions = [
            200 => 'OK',
            201 => 'Created successfully',
            400 => 'Bad request',
            401 => 'Unauthorized',
            403 => 'Forbidden',
            404 => 'Not Found',
            422 => 'Validation failed',
            500 => 'Internal server error',
        ];

        foreach ($definitions as $def) {
            if (! isset($def['statusCode'])) {
                throw new InvalidArgumentException("Each response definition must include a 'statusCode'");
            }

            $status = $def['statusCode'];
            $modelSchema = $def['modelSchema'] ?? null;
            $isArray = $def['isArray'] ?? false;
            $isPaginated = $def['isPaginated'] ?? false;
            $example = $def['example'] ?? null;

            $content = match (true) {
                // ✅ Paginated response
                $isPaginated && $modelSchema => new JsonContent(
                    type: 'object',
                    properties: [
                        new Property(
                            property: 'data',
                            type: 'array',
                            items: new Items(ref: "#/components/schemas/{$modelSchema}")
                        ),
                        new Property(
                            property: 'meta',
                            type: 'object',
                            properties: [
                                new Property(property: 'current_page', type: 'integer', example: 1),
                                new Property(property: 'per_page', type: 'integer', example: 15),
                                new Property(property: 'total', type: 'integer', example: 100),
                                new Property(property: 'last_page', type: 'integer', example: 7),
                                new Property(property: 'from', type: 'integer', example: 1),
                                new Property(property: 'to', type: 'integer', example: 7),
                                new Property(property: 'next_page_url', type: 'string', example: config('app.url').'/resources?page=2'),
                                new Property(property: 'prev_page_url', type: 'string', example: config('app.url').'/resources?page=1'),
                            ]
                        ),
                    ]
                ),

                (bool) $modelSchema => (function () use ($modelSchema, $isArray): JsonContent {
                    $refPath = "#/components/schemas/{$modelSchema}";

                    return $isArray
                        ? new JsonContent(type: 'array', items: new Items(ref: $refPath))
                        : new JsonContent(ref: $refPath);
                })(),

                (bool) $example => new JsonContent(
                    type: is_array($example) ? 'object' : 'string',
                    example: $example
                ),

                default => new JsonContent(
                    type: 'object',
                    properties: [
                        new Property(property: 'message', type: 'string', example: $defaultDescriptions[$status] ?? 'Response'),
                    ]
                ),
            };

            $this->responses[] = new OAResponse(
                response: $status,
                description: $defaultDescriptions[$status] ?? 'Response',
                content: $content
            );
        }

        // Always include 500 once (if not explicitly defined)
        if (! collect($this->responses)->contains(fn (OAResponse $r) => $r->response === 500)) {
            $this->responses[] = new OAResponse(
                response: 500,
                description: $defaultDescriptions[500],
                content: new JsonContent(
                    type: 'object',
                    properties: [
                        new Property(property: 'message', type: 'string', example: $defaultDescriptions[500]),
                    ]
                )
            );
        }
    }
}

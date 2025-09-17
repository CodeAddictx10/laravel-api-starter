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

            // Build schema if modelSchema provided
            if ($modelSchema) {
                $refPath = "#/components/schemas/{$modelSchema}";
                $content = $isArray
                    ? new JsonContent(type: 'array', items: new Items(ref: $refPath))
                    : new JsonContent(ref: $refPath);
            } else {
                $content = new JsonContent(
                    type: 'object',
                    properties: [
                        new Property(property: 'success', type: 'boolean', example: $status < 400),
                        new Property(property: 'message', type: 'string', example: $defaultDescriptions[$status] ?? 'Response'),
                    ]
                );
            }

            $this->responses[] = new OAResponse(
                response: $status,
                description: $defaultDescriptions[$status] ?? 'Response',
                content: $content
            );
        }

        // Always include 500 once
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

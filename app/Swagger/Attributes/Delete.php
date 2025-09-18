<?php

declare(strict_types=1);

namespace App\Swagger\Attributes;

use Attribute;
use OpenApi\Attributes\Delete as OADelete;

#[Attribute(Attribute::TARGET_METHOD)]
final class Delete extends OADelete
{
    public function __construct(
        string $path,
        string $tags,
        string $summary,
        array $responses = [],
        array $parameters = []
    ) {
        $defaultResponses = [
            ['statusCode' => 204, 'description' => 'Resource deleted successfully', 'example' => ['message' => 'Resource deleted successfully']],
            ['statusCode' => 404, 'description' => 'Resource not found', 'example' => ['message' => 'Resource not found']]
        ];

        $responses = array_merge($defaultResponses, $responses);

        $builder = new Response($defaultResponses);


        $parameters = Param::build($path, $parameters);


        parent::__construct(
            path: $path,
            summary: $summary,
            tags: [$tags],
            responses: $builder->responses,
            parameters: $parameters
        );
    }
}

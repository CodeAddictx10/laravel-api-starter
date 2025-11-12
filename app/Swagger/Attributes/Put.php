<?php

declare(strict_types=1);

namespace App\Swagger\Attributes;

use Attribute;
use OpenApi\Attributes\Put as OAPut;

#[Attribute(Attribute::TARGET_METHOD)]
final class Put extends OAPut
{
    public function __construct(
        string $path,
        string $tags,
        string $summary,
        array $responses = [],
        array $parameters = [],
        ?string $requestBody = null,
        bool $auth = false,
    ) {
        $builder = new Response($responses);

        $parameters = Param::build($path, $parameters);

        parent::__construct(
            path: $path,
            summary: $summary,
            tags: [$tags],
            responses: $builder->responses,
            requestBody: $requestBody ? RequestBody::create($requestBody) : null,
            parameters: $parameters,
            security: $auth ? config('l5-swagger.defaults.securityDefinitions.security', []) : []
        );
    }
}

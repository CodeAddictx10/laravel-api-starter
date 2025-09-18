<?php

declare(strict_types=1);

namespace App\Swagger\Attributes;

use Attribute;
use OpenApi\Attributes\Patch as OAPatch;

#[Attribute(Attribute::TARGET_METHOD)]
final class Patch extends OAPatch
{
    public function __construct(
        string $path,
        string $tags,
        string $summary,
        array $responses = [],
        array $parameters = [],
        ?string $requestBody = null
    ) {
        $builder = new Response($responses);

        $parameters = Param::build($path, $parameters);

        parent::__construct(
            path: $path,
            summary: $summary,
            tags: [$tags],
            responses: $builder->responses,
            requestBody: $requestBody ? RequestBody::create($requestBody) : null,
            parameters: $parameters
        );
    }
}

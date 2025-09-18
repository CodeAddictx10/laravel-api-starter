<?php

declare(strict_types=1);

namespace App\Swagger\Attributes;

use Attribute;
use OpenApi\Attributes\Get as OAGet;

#[Attribute(Attribute::TARGET_METHOD)]
final class Get extends OAGet
{
    public function __construct(
        string $path,
        string $tags,
        string $summary,
        array $responses = [],
        array $parameters = []
    ) {
        $builder = new Response($responses);

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

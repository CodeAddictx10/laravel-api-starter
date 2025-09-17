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
        array $responses = []
    ) {
        $builder = new Response($responses);

        parent::__construct(
            path: $path,
            summary: $summary,
            tags: [$tags],
            responses: $builder->responses
        );
    }
}

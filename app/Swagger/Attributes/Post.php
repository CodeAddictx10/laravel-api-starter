<?php

declare(strict_types=1);

namespace App\Swagger\Attributes;

use Attribute;
use OpenApi\Attributes\Post as OAPost;

#[Attribute(Attribute::TARGET_METHOD)]
final class Post extends OAPost
{
    public function __construct(
        string $path,
        string $tags,
        string $summary,
        array $responses = [],
        ?string $requestBody = null
    ) {
        $builder = new Response($responses);

        parent::__construct(
            path: $path,
            summary: $summary,
            tags: [$tags],
            responses: $builder->responses,
            requestBody: $requestBody ? RequestBody::create($requestBody) : null
        );
    }
}

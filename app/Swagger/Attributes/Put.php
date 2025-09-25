<?php

declare(strict_types=1);

namespace App\Swagger\Attributes;

use OpenApi\Attributes\JsonContent;
use OpenApi\Attributes\RequestBody as OARequestBody;

final class RequestBody
{
    public static function create(string $schema): OARequestBody
    {
        return new OARequestBody(
            required: true,
            content: new JsonContent(ref: "#/components/schemas/{$schema}")
        );
    }
}

<?php

declare(strict_types=1);

namespace App\Http\Controllers;

/**
 * @OA\Info(
 *     title="SCM Data Upload API",
 *     version="1.0.0",
 *     description="API for SCM Data Upload System",
 *
 *     @OA\Contact(
 *         email="admin@example.com"
 *     )
 * )
 *
 * @OA\Server(
 *     url=L5_SWAGGER_CONST_HOST,
 *     description="API Server"
 * )
 *
 * @OA\SecurityScheme(
 *     securityScheme="sanctum",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="JWT",
 *     description="Enter token in format (Bearer <token>)"
 * )
 */
abstract class Controller
{
    //
}

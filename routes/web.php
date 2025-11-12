<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/docs', function () {
    return view('doc');
});


Route::get('/docs/api-docs.yaml', function () {
    $path = storage_path('api-docs/api-docs.yaml');
    if (!file_exists($path)) {
        abort(404);
    }

    return response()->file($path, [
        'Content-Type' => 'application/yaml',
    ]);
});

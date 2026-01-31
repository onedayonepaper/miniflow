<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// API Documentation
Route::get('/docs/openapi.yaml', function () {
    $path = base_path('docs/openapi.yaml');

    if (!file_exists($path)) {
        abort(404);
    }

    return response()->file($path, [
        'Content-Type' => 'application/x-yaml',
    ]);
});

Route::get('/docs', function () {
    return view('docs.swagger');
});

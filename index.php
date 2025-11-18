<?php

$request_uri = $_SERVER['REQUEST_URI'];
$request_method = $_SERVER['REQUEST_METHOD'];

$uri_parts = explode('/', trim($request_uri, '/'));

// Remove the project folder name from the URI parts
if (isset($uri_parts[0]) && ($uri_parts[0] === 'index.php' || $uri_parts[0] === 'device_sandbox_backend')) {
    array_shift($uri_parts);
}

$endpoint = isset($uri_parts[0]) ? $uri_parts[0] : '';

// Set JSON header
header('Content-Type: application/json');

switch ($endpoint) {
    case '':
        echo json_encode([
            'message' => 'API is working',
        ]);
        break;
    default:
        http_response_code(404);
        echo json_encode([
            'message' => 'Api not found',
        ]);
        break;
}

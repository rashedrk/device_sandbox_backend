<?php

$request_uri = $_SERVER['REQUEST_URI'];
$request_method = $_SERVER['REQUEST_METHOD'];

// Parse query string to get clean path
$request_uri = parse_url($request_uri, PHP_URL_PATH);
$uri_parts = explode('/', trim($request_uri, '/'));

// Remove the project folder name from the URI parts
if (isset($uri_parts[0]) && ($uri_parts[0] === 'index.php' || $uri_parts[0] === 'device_sandbox_backend')) {
    array_shift($uri_parts);
}

$endpoint = isset($uri_parts[0]) ? $uri_parts[0] : '';
$params = array_slice($uri_parts, 1);

// Set JSON header
header('Content-Type: application/json');

switch ($endpoint) {
    case '':
        echo json_encode([
            'message' => 'API is working',
            'version' => '1.0',
            'endpoints' => [
                'GET /devices' => 'Get all devices',
                'GET /devices/{id}' => 'Get device by ID'
            ]
        ]);
        break;

    case 'devices':
        require_once __DIR__ . '/api/devices.php';
        $api = new DeviceAPI();
        $api->handleRequest($request_method, $params);
        break;

    default:
        http_response_code(404);
        echo json_encode([
            'error' => 'Not found',
            'message' => 'API endpoint not found'
        ]);
        break;
}

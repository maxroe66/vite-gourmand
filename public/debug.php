<?php
// Script de debug pour diagnostiquer les problèmes POST
header('Content-Type: application/json');

$debug = [
    'method' => $_SERVER['REQUEST_METHOD'] ?? 'not set',
    'content_type' => $_SERVER['CONTENT_TYPE'] ?? 'not set',
    'content_length' => $_SERVER['CONTENT_LENGTH'] ?? 'not set',
    'raw_input' => file_get_contents('php://input'),
    'post_data' => $_POST,
    'get_data' => $_GET,
    'server_vars' => [
        'HTTP_CONTENT_TYPE' => $_SERVER['HTTP_CONTENT_TYPE'] ?? 'not set',
        'REQUEST_URI' => $_SERVER['REQUEST_URI'] ?? 'not set',
        'QUERY_STRING' => $_SERVER['QUERY_STRING'] ?? 'not set',
    ],
    'php_input_available' => is_readable('php://input'),
    'json_decode_test' => json_decode(file_get_contents('php://input'), true),
    'json_last_error' => json_last_error_msg(),
];

echo json_encode($debug, JSON_PRETTY_PRINT);
?>
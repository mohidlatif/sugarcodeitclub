<?php
header('Content-Type: application/json; charset=utf-8');

$file = __DIR__ . '/../data/learn-content.json';

if (!file_exists($file)) {
    http_response_code(404);
    echo json_encode(['error' => 'Learn content not found']);
    exit;
}

readfile($file);

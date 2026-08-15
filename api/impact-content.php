<?php
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$file = dirname(__DIR__) . '/data/impact-content.json';
if (!is_file($file)) {
    http_response_code(404);
    echo json_encode(['error' => 'Impact content not found']);
    exit;
}

$data = file_get_contents($file);
if ($data === false) {
    http_response_code(500);
    echo json_encode(['error' => 'Could not read impact content']);
    exit;
}

echo $data;

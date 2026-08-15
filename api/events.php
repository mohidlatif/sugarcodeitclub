<?php
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

$file = dirname(__DIR__) . '/data/events.json';
if (!is_file($file)) {
    echo '[]';
    exit;
}

$data = file_get_contents($file);
if ($data === false) {
    http_response_code(500);
    echo '[]';
    exit;
}

echo $data;

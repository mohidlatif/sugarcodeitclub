<?php
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

$file = dirname(__DIR__) . '/data/roadmap-content.json';

if (!is_file($file)) {
    echo json_encode(['items' => []]);
    exit;
}

$data = json_decode((string) file_get_contents($file), true);
if (!is_array($data)) {
    $data = ['items' => []];
}

echo json_encode($data, JSON_UNESCAPED_SLASHES);

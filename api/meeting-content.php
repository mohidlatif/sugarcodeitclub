<?php
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

$file = dirname(__DIR__) . '/data/meeting-content.json';
$data = [];

if (is_file($file)) {
    $decoded = json_decode((string) file_get_contents($file), true);
    if (is_array($decoded)) {
        $data = $decoded;
    }
}

echo json_encode([
    'content' => isset($data['content']) ? (string) $data['content'] : '',
    'minutes' => isset($data['minutes']) ? (string) $data['minutes'] : '',
    'home_label' => isset($data['home_label']) ? (string) $data['home_label'] : '',
    'home_date' => isset($data['home_date']) ? (string) $data['home_date'] : '',
    'home_title' => isset($data['home_title']) ? (string) $data['home_title'] : '',
    'home_details' => isset($data['home_details']) ? (string) $data['home_details'] : '',
    'updated' => isset($data['updated']) ? (string) $data['updated'] : ''
], JSON_UNESCAPED_SLASHES);

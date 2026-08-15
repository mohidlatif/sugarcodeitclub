<?php
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

$file = dirname(__DIR__) . '/data/photos.json';
if (!is_file($file)) {
    echo '[]';
    exit;
}

$data = json_decode((string) file_get_contents($file), true);
if (!is_array($data)) {
    $data = [];
}

$section = isset($_GET['section']) ? strtolower(trim((string) $_GET['section'])) : '';
if ($section !== '') {
    $data = array_values(array_filter($data, function ($photo) use ($section) {
        return isset($photo['section']) && strtolower((string) $photo['section']) === $section;
    }));
}

echo json_encode($data, JSON_UNESCAPED_SLASHES);

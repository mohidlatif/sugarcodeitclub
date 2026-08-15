<?php
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

$root = dirname(__DIR__);
$file = $root . '/data/projects.json';
$projects = [];

if (is_file($file)) {
    $decoded = json_decode((string) file_get_contents($file), true);
    if (is_array($decoded)) {
        $projects = $decoded;
    }
}

$category = strtolower(trim((string) ($_GET['category'] ?? '')));
$allowed = ['arduino', 'programming', 'engineering'];

if (in_array($category, $allowed, true)) {
    $projects = array_values(array_filter($projects, function ($project) use ($category) {
        return isset($project['category']) && $project['category'] === $category;
    }));
}

echo json_encode($projects, JSON_UNESCAPED_SLASHES);

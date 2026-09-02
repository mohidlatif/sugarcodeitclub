<?php

header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

$projectsFile = dirname(__DIR__) . '/data/projects.json';

if (!is_file($projectsFile)) {
    echo json_encode([]);
    exit;
}

$json = file_get_contents($projectsFile);

if ($json === false) {
    echo json_encode([]);
    exit;
}

$projects = json_decode($json, true);

if (!is_array($projects)) {
    echo json_encode([]);
    exit;
}

$category = strtolower(trim($_GET['category'] ?? ''));

if ($category !== '') {
    $projects = array_values(
        array_filter($projects, function ($project) use ($category) {
            $projectCategory = strtolower(
                trim((string) ($project['category'] ?? ''))
            );

            return $projectCategory === $category;
        })
    );
}

echo json_encode(
    $projects,
    JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
);

exit;
<?php

header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

$eventsFile = dirname(__DIR__) . '/data/events.json';

if (!file_exists($eventsFile)) {
    echo json_encode([]);
    exit;
}

$json = file_get_contents($eventsFile);

if ($json === false) {
    echo json_encode([]);
    exit;
}

$data = json_decode($json, true);

if (!is_array($data)) {
    echo json_encode([]);
    exit;
}

echo json_encode($data);
exit;
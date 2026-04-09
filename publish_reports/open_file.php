<?php
header('Content-Type: application/json');

include_once __DIR__ . "/config.php";

// open_file.php?report=bbbb&year=2025&month=04&day=15&name=success.json

$report = $_GET['report'] ?? '';
$year   = $_GET['year'] ?? '';
$month  = $_GET['month'] ?? '';
$day    = $_GET['day'] ?? '';
$name   = $_GET['name'] ?? '';

// Validate name: alphanumeric, hyphens, underscores, dots only (allowlist for filename)
if (!preg_match('/^\d+$/', $year) || !preg_match('/^\d+$/', $month) || !preg_match('/^\d+$/', $day)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid name format']);
    exit;
}

// Return false if any required parameter is empty
if (empty($report) || empty($year) || empty($month) || empty($day) || empty($name)) {
    echo json_encode([]);
    exit;
}

// Validate name: alphanumeric, hyphens, underscores, dots only (allowlist for filename)
if (!preg_match('/^[a-zA-Z0-9_.-]+$/', $name) || !preg_match('/^[a-zA-Z0-9_.-]+$/', $report)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid name format']);
    exit;
}

// @phpstan-ignore constant.notFound
$file_path = PUBLISH_REPORTS_DIR_BY_DAY . "/" . $year . "/" . $month . "/" . $day . "/" . $report . "/" . $name;

$data = [];
if (file_exists($file_path)) {
    $data = json_decode(file_get_contents($file_path), true);
}

echo json_encode($data);

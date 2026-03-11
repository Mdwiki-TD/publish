<?php
header('Content-Type: application/json');

$publish_reports_path = getenv("PUBLISH_REPORTS_PATH") ?: ($_ENV['PUBLISH_REPORTS_PATH'] ?? "");
// ---
if (empty($publish_reports_path)) {
    error_log("PUBLISH_REPORTS_PATH is not set");
    // ---
    $env = getenv('APP_ENV') ?: ($_ENV['APP_ENV'] ?? 'development');
    $publish_reports_path = ($env === 'production')
        ? getenv("HOME") . "/data/publish_reports_data"
        : 'I:/mdwiki/publish-repo/publish_reports_data';
};

$reports_by_day = $publish_reports_path . '/reports_by_day';

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

$file_path = $reports_by_day . "/" . $year . "/" . $month . "/" . $day . "/" . $report . "/" . $name;

$data = [];
if (file_exists($file_path)) {
    $data = json_decode(file_get_contents($file_path), true);
}

echo json_encode($data);

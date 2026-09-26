<?php

// Enable error reporting for debugging
if (isset($_REQUEST['test'])) {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
}

$publishReportsPath = getenv("PUBLISH_REPORTS_PATH") ?: ($_ENV['PUBLISH_REPORTS_PATH'] ?? "");

if (empty($publishReportsPath)) {
    error_log("PUBLISH_REPORTS_PATH is not set");
    $publishReportsPath = getenv("HOME") . "/data/publish_reports_data";
};

define('PUBLISH_REPORTS_DIR_BY_DAY', $publishReportsPath . '/reports_by_day/');

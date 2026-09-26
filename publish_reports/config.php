<?php

$publishReportsPath = getenv("PUBLISH_REPORTS_PATH") ?: ($_ENV['PUBLISH_REPORTS_PATH'] ?? "");

if (empty($publishReportsPath)) {
    error_log("PUBLISH_REPORTS_PATH is not set");
    $publishReportsPath = getenv("HOME") . "/data/publish_reports_data";
};

define('PUBLISH_REPORTS_DIR_BY_DAY', $publishReportsPath . '/reports_by_day/');

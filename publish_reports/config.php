<?php

$publish_reports_path = getenv("PUBLISH_REPORTS_PATH") ?: ($_ENV['PUBLISH_REPORTS_PATH'] ?? "");

if (empty($publish_reports_path)) {
    error_log("PUBLISH_REPORTS_PATH is not set");
    $publish_reports_path = getenv("HOME") . "/data/publish_reports_data";
};

define('PUBLISH_REPORTS_DIR_BY_DAY', $publish_reports_path . '/reports_by_day/');

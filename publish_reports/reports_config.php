<?php
// publish_reports/reports_config.php
// namespace PublishReports;
// class Config

// Enable error reporting for debugging
if (isset($_REQUEST['test'])) {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
}

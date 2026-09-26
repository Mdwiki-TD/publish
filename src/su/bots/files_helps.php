<?php

namespace Publish\FilesHelps;
/*
Usage:
use function Publish\FilesHelps\to_do;
use function Publish\FilesHelps\check_dirs;
*/

use function Publish\Helps\pub_test_print;

function to_do($tab, $fileName, $randId)
{
    $mainDirByDay = check_dirs($randId, 'reports_by_day');
    $tab['time'] = time();
    $tab['time_date'] = date("Y-m-d H:i:s");
    try {
        // dump $tab to file in folder to_do
        $file_j = $mainDirByDay . "/$fileName.json";
        file_put_contents($file_j, json_encode($tab, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    } catch (\Exception $e) {
        pub_test_print($e->getMessage());
    }
}

function check_dirs($randId, $reportsDirMain)
{
    // /data/project/mdwiki/data/publish_reports
    $publishReportsPath = getenv("PUBLISH_REPORTS_PATH") ?: ($_ENV['PUBLISH_REPORTS_PATH'] ?? "");

    if (empty($publishReportsPath)) {
        error_log("PUBLISH_REPORTS_PATH is not set");
        $publishReportsPath = getenv("HOME") . "/data/publish_reports_data";
    };
    if (!is_dir($publishReportsPath)) {
        mkdir($publishReportsPath, 0755, true);
    }
    $reportsDir = "$publishReportsPath/$reportsDirMain/";
    if (!is_dir($reportsDir)) {
        mkdir($reportsDir, 0755, true);
    }
    $yearDir = $reportsDir . date("Y");
    if (!is_dir($yearDir)) {
        mkdir($yearDir, 0755, true);
    }
    $monthDir = $yearDir . "/" . date("m");
    if (!is_dir($monthDir)) {
        mkdir($monthDir, 0755, true);
    }
    $dayDir = $monthDir . "/" . date("d");
    if (!is_dir($dayDir)) {
        mkdir($dayDir, 0755, true);
    }
    $main1_dir = $dayDir . "/" . $randId;
    if (!is_dir($main1_dir)) {
        mkdir($main1_dir, 0755, true);
    }
    return $main1_dir;
}

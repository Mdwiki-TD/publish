<?php

namespace Publish\FilesHelps;
/*
Usage:
use function Publish\FilesHelps\to_do;
use function Publish\FilesHelps\check_dirs;
*/

use function Publish\Helps\pub_test_print;

// $rand_id = rand(0, 999999999);
$rand_id = time() .  "-" . bin2hex(random_bytes(6));

$main_dir_by_day = check_dirs($rand_id, "reports_by_day");

function to_do($tab, $file_name)
{
    global $main_dir_by_day; // $main_dir,
    // ---
    $tab['time'] = time();
    $tab['time_date'] = date("Y-m-d H:i:s");
    // ---
    /*
    try {
        // dump $tab to file in folder to_do
        $file_j = $main_dir . "/$file_name.json";
        // ---
        file_put_contents($file_j, json_encode($tab, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    } catch (\Exception $e) {
        pub_test_print($e->getMessage());
    }*/
    // ---
    try {
        // dump $tab to file in folder to_do
        $file_j = $main_dir_by_day . "/$file_name.json";
        // ---
        file_put_contents($file_j, json_encode($tab, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    } catch (\Exception $e) {
        pub_test_print($e->getMessage());
    }
}

function check_dirs($rand_id, $reports_dir_main)
{
    // $publish_reports = __DIR__ . "/../../publish_reports/";
    // if (substr(__DIR__, 0, 2) == 'I:') $publish_reports = "I:/mdwiki/publish-repo/src/publish_reports/";
    // ---
	$html_dir = getenv("HOME") ?: 'I:/mdwiki/publish-repo/src';
    // ---
    $publish_reports = $html_dir . "/publish_reports/";
    // ---
    $reports_dir = "$publish_reports/$reports_dir_main/";
    // ---
    if (!is_dir($reports_dir)) {
        mkdir($reports_dir, 0755, true);
    }
    // ---
    $year_dir = $reports_dir . date("Y");
    // ---
    if (!is_dir($year_dir)) {
        mkdir($year_dir, 0755, true);
    }
    // ---
    $month_dir = $year_dir . "/" . date("m");
    // ---
    if (!is_dir($month_dir)) {
        mkdir($month_dir, 0755, true);
    }
    // ---
    $day_dir = $month_dir . "/" . date("d");
    // ---
    if (!is_dir($day_dir)) {
        mkdir($day_dir, 0755, true);
    }
    // ---
    $main1_dir = $day_dir . "/" . $rand_id;
    // ---
    if (!is_dir($main1_dir)) {
        mkdir($main1_dir, 0755, true);
    }
    // ---
    return $main1_dir;
}

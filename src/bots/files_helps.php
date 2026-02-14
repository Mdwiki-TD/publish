<?php

declare(strict_types=1);

/**
 * File-based logging for publish operations.
 *
 * This module handles writing publish operation logs to JSON files
 * organized by date (year/month/day). Each operation gets a unique
 * directory with timestamped JSON files.
 *
 * ARCHITECTURAL WARNING: Global state is set at file include time,
 * which can cause race conditions with concurrent requests.
 * @see ANALYSIS_REPORT.md LOG-003
 *
 * @package Publish\FilesHelps
 * @author  MDWiki Team
 * @since   1.0.0
 *
 * @example
 * use function Publish\FilesHelps\to_do;
 *
 * $tab = ['title' => 'Article', 'result' => 'success'];
 * to_do($tab, 'success'); // Writes to reports_by_day/YYYY/MM/DD/{unique_id}/success.json
 */

namespace Publish\FilesHelps;

use function Publish\Helps\pub_test_print;

/**
 * Generates a unique identifier for the current request.
 *
 * RACE CONDITION WARNING: This is set at file include time, not
 * function call time. Concurrent requests may share the same ID
 * if they include the file within the same second.
 * @see ANALYSIS_REPORT.md LOG-003
 *
 * @var string
 */
$rand_id = time() . "-" . bin2hex(random_bytes(6));

/**
 * Pre-created directory path for logging the current request.
 *
 * RACE CONDITION WARNING: Directory is created at include time,
 * meaning all to_do() calls in the same request share this directory.
 *
 * @var string
 */
$main_dir_by_day = check_dirs($rand_id, "reports_by_day");

/**
 * Logs operation data to a JSON file.
 *
 * Writes the provided data array to a JSON file in the pre-created
 * log directory. Adds timestamp information to the data.
 *
 * @param array<string, mixed> $tab       The data to log
 * @param string               $file_name The base name for the log file (without .json)
 *
 * @return void
 *
 * @example
 * to_do(['title' => 'Article', 'user' => 'John'], 'success');
 * // Creates: reports_by_day/2024/01/15/{unique_id}/success.json
 */
function to_do(array $tab, string $file_name): void
{
    global $main_dir_by_day;

    $tab['time'] = time();
    $tab['time_date'] = date("Y-m-d H:i:s");

    try {
        $file_j = $main_dir_by_day . "/{$file_name}.json";
        file_put_contents(
            $file_j,
            json_encode($tab, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
        );
    } catch (\Exception $e) {
        pub_test_print($e->getMessage());
    }
}

/**
 * Creates the directory structure for logging operations.
 *
 * Creates a nested directory structure: reports_dir/YYYY/MM/DD/unique_id/
 * All directories are created with 0755 permissions.
 *
 * SECURITY NOTE: 0755 permissions allow world read access.
 * @see ANALYSIS_REPORT.md SEC-009
 *
 * @param string $rand_id          Unique identifier for this request's log directory
 * @param string $reports_dir_main Base directory name for reports
 *
 * @return string The full path to the created unique directory
 *
 * @example
 * $dir = check_dirs("1705312800-abc123", "reports_by_day");
 * // Returns: /path/to/publish_reports/reports_by_day/2024/01/15/1705312800-abc123
 */
function check_dirs(string $rand_id, string $reports_dir_main): string
{
    $html_dir = getenv("HOME") ?: 'I:/mdwiki/publish-repo/src';

    $publish_reports = $html_dir . "/publish_reports/";
    $reports_dir = "{$publish_reports}/{$reports_dir_main}/";

    // Create base reports directory
    if (!is_dir($reports_dir)) {
        mkdir($reports_dir, 0755, true);
    }

    // Create year directory
    $year_dir = $reports_dir . date("Y");
    if (!is_dir($year_dir)) {
        mkdir($year_dir, 0755, true);
    }

    // Create month directory
    $month_dir = $year_dir . "/" . date("m");
    if (!is_dir($month_dir)) {
        mkdir($month_dir, 0755, true);
    }

    // Create day directory
    $day_dir = $month_dir . "/" . date("d");
    if (!is_dir($day_dir)) {
        mkdir($day_dir, 0755, true);
    }

    // Create unique request directory
    $main1_dir = $day_dir . "/" . $rand_id;
    if (!is_dir($main1_dir)) {
        mkdir($main1_dir, 0755, true);
    }

    return $main1_dir;
}

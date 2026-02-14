<?php

declare(strict_types=1);

/**
 * Revision ID lookup for MDWiki articles.
 *
 * This module provides functions to look up MediaWiki revision IDs
 * for source articles. Revision IDs are used in edit summaries to
 * track which version of the source article was translated.
 *
 * @package Publish\Revids
 * @author  MDWiki Team
 * @since   1.0.0
 *
 * @example
 * use function Publish\Revids\get_revid;
 * use function Publish\Revids\get_revid_db;
 *
 * $revid = get_revid("Heart disease");
 * // Returns revision ID from local JSON file
 */

namespace Publish\Revids;

use function Publish\Helps\pub_test_print;
use function Publish\Helps\get_url_curl;

/**
 * Looks up revision ID for an article from the MDWiki API.
 *
 * Queries the MDWiki toolforge API to get the current revision ID
 * for an article. Falls back to localhost API in development.
 *
 * PERFORMANCE NOTE: Makes HTTP request on each call.
 *
 * @param string $sourcetitle The MDWiki article title
 *
 * @return string The revision ID, or empty string if not found
 *
 * @example
 * $revid = get_revid_db("Heart disease");
 * // Returns: "12345" or "" if not found
 */
function get_revid_db(string $sourcetitle): string
{
    $params = [
        "get" => "revids",
        "title" => $sourcetitle
    ];

    $serverName = $_SERVER['SERVER_NAME'] ?? "localhost";

    if ($serverName === "localhost") {
        $url = "http://localhost:9001/api?" . http_build_query($params, '', '&', PHP_QUERY_RFC3986);
        $json = file_get_contents($url);
    } else {
        $url = "https://mdwiki.toolforge.org/api.php?" . http_build_query($params, '', '&', PHP_QUERY_RFC3986);
        $json = get_url_curl($url);
    }

    $json = json_decode($json, true);

    $results = array_column($json["results"] ?? [], "revid", "title");

    $revid = $results[$sourcetitle] ?? "";

    return (string) $revid;
}

/**
 * Looks up revision ID for an article from a local JSON file.
 *
 * Reads from all_pages_revids.json which contains a mapping of
 * article titles to their revision IDs. This is faster than
 * the API call but may be out of date.
 *
 * PERFORMANCE NOTE: Reads and parses JSON file on each call.
 * @see ANALYSIS_REPORT.md PERF-003
 *
 * @param string $sourcetitle The MDWiki article title
 *
 * @return string The revision ID, or empty string if not found
 *
 * @example
 * $revid = get_revid("Heart disease");
 * // Returns: "12345" or "" if not found
 */
function get_revid(string $sourcetitle): string
{
    $revids_file = __DIR__ . '/all_pages_revids.json';

    if (!file_exists($revids_file)) {
        $revids_file = __DIR__ . '/../all_pages_revids.json';
    }

    try {
        $json = json_decode(file_get_contents($revids_file), true);
        $revid = $json[$sourcetitle] ?? "";
        return (string) $revid;
    } catch (\Exception $e) {
        pub_test_print($e->getMessage());
    }

    return "";
}

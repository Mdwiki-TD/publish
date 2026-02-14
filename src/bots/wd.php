<?php

declare(strict_types=1);

/**
 * Wikidata integration for sitelink management.
 *
 * This module handles linking translated Wikipedia articles to their
 * corresponding Wikidata items via the wbsetsitelink API. It manages
 * QID lookups and OAuth-authenticated Wikidata API calls.
 *
 * @package Publish\WD
 * @author  MDWiki Team
 * @since   1.0.0
 *
 * @see https://www.wikidata.org/w/api.php?action=help&modules=wbsetsitelink
 *
 * @example
 * use function Publish\WD\LinkToWikidata;
 * use function Publish\WD\GetQidForMdtitle;
 *
 * $result = LinkToWikidata("Heart", "ar", "JohnDoe", "قلب", $key, $secret);
 * // Links Arabic article "قلب" to the Wikidata item for "Heart"
 */

namespace Publish\WD;

include_once __DIR__ . '/../include.php';

use function Publish\GetToken\post_params;
use function Publish\MdwikiSql\fetch_query;
use function Publish\AccessHelps\get_access_from_db;
use function Publish\AccessHelpsNew\get_access_from_db_new;
use function Publish\Helps\pub_test_print;
use function Publish\Helps\get_url_curl;

/**
 * Looks up the Wikidata QID for an MDWiki article title.
 *
 * Queries the local qids table which stores mappings between
 * MDWiki article titles and their corresponding Wikidata QIDs.
 *
 * @param string $title The MDWiki article title
 *
 * @return array<int, array{qid: string}> Array of results containing QID
 *
 * @example
 * $result = GetQidForMdtitle("Heart");
 * $qid = $result[0]['qid'] ?? ''; // e.g., "Q9299"
 */
function GetQidForMdtitle(string $title): array
{
    $query = <<<SQL
        SELECT qid FROM qids WHERE title = ?
    SQL;

    $params = [$title];

    $result = fetch_query($query, $params);

    return $result;
}

/**
 * Fetches page information from Wikipedia API (legacy method).
 *
 * Uses the REST API summary endpoint to get basic page info.
 * This method is less reliable than the query API.
 *
 * @deprecated Use GetTitleInfo() instead
 *
 * @param string $targettitle The article title
 * @param string $lang        The language code
 *
 * @return array<string, mixed>|null The page info or null on failure
 */
function GetTitleInfoOld(string $targettitle, string $lang): ?array
{
    $targettitle = urlencode($targettitle);

    $url = "https://{$lang}.wikipedia.org/api/rest_v1/page/summary/{$targettitle}";

    pub_test_print("GetTitleInfo url: $url");

    try {
        $result = get_url_curl($url);
        pub_test_print("GetTitleInfo result: $result");
        $result = json_decode($result, true);
    } catch (\Exception $e) {
        pub_test_print("GetTitleInfo: $e");
        $result = null;
    }

    return $result;
}

/**
 * Fetches page information from Wikipedia API.
 *
 * Uses the MediaWiki query API to get basic page information
 * including page ID and namespace.
 *
 * @param string $targettitle The article title
 * @param string $lang        The language code
 *
 * @return array<string, mixed>|null The page info or null on failure
 *
 * @example
 * $info = GetTitleInfo("قلب", "ar");
 * // Returns: ['pageid' => 12345, 'ns' => 0, 'title' => 'قلب']
 */
function GetTitleInfo(string $targettitle, string $lang): ?array
{
    $params = [
        "action" => "query",
        "format" => "json",
        "titles" => $targettitle,
        "utf8" => 1,
        "formatversion" => "2"
    ];

    $url = "https://{$lang}.wikipedia.org/w/api.php" . "?" . http_build_query($params, '', '&', PHP_QUERY_RFC3986);

    pub_test_print("GetTitleInfo url: $url");

    try {
        $result = get_url_curl($url);
        pub_test_print("GetTitleInfo result: $result");
        $result = json_decode($result, true);

        // Extract first page from results
        $result = $result['query']['pages'][0] ?? null;
    } catch (\Exception $e) {
        pub_test_print("GetTitleInfo: $e");
        $result = null;
    }

    return $result;
}

/**
 * Creates a sitelink between a Wikidata item and a Wikipedia article.
 *
 * Uses the wbsetsitelink API to link the specified article to its
 * corresponding Wikidata item. If QID is provided, links by ID;
 * otherwise, links by source title and site.
 *
 * @param string $qid           The Wikidata QID (optional if sourcetitle provided)
 * @param string $lang          The target language code
 * @param string $sourcetitle   The source MDWiki title (for lookup if no QID)
 * @param string $targettitle   The target Wikipedia article title
 * @param string $access_key    OAuth access token key
 * @param string $access_secret OAuth access token secret
 *
 * @return array<string, mixed> The API response, containing:
 *                              - success: 1 on success
 *                              - error: error details on failure
 */
function LinkIt(
    string $qid,
    string $lang,
    string $sourcetitle,
    string $targettitle,
    string $access_key,
    string $access_secret
): array {
    $https_domain = "https://www.wikidata.org";

    $apiParams = [
        "action" => "wbsetsitelink",
        "linktitle" => $targettitle,
        "linksite" => "{$lang}wiki",
    ];

    if (!empty($qid)) {
        // Link by QID if available
        $apiParams["id"] = $qid;
    } else {
        // Otherwise link by source title
        $apiParams["title"] = $sourcetitle;
        $apiParams["site"] = "enwiki";
    }

    $response = post_params($apiParams, $https_domain, $access_key, $access_secret);

    $Result = json_decode($response, true) ?? [];

    if (isset($Result['error'])) {
        pub_test_print("post_params: Result->error: " . json_encode($Result['error']));
    }

    if ($Result === null) {
        pub_test_print("post_params: Error: " . json_last_error() . " " . json_last_error_msg());
        pub_test_print("response:");
        pub_test_print($response);
    }

    return $Result;
}

/**
 * Retrieves OAuth access credentials for a user.
 *
 * Falls back to database lookup if credentials not provided directly.
 * This allows the function to work with or without explicit credentials.
 *
 * @param string      $user          The username
 * @param string|null $access_key    Provided access key (optional)
 * @param string|null $access_secret Provided access secret (optional)
 *
 * @return array{0: string, 1: string}|null Array of [key, secret] or null if not found
 */
function getAccessCredentials(
    string $user,
    ?string $access_key,
    ?string $access_secret
): ?array {
    if (!$access_key || !$access_secret) {
        $access = get_access_from_db_new($user);

        if ($access === null) {
            $access = get_access_from_db($user);
        }

        if ($access === null) {
            pub_test_print("user = $user");
            pub_test_print("access == null");
            return null;
        }

        $access_key = $access['access_key'];
        $access_secret = $access['access_secret'];
    }

    return [$access_key, $access_secret];
}

/**
 * Links a translated Wikipedia article to its Wikidata item.
 *
 * This is the main entry point for Wikidata sitelink operations.
 * It looks up the QID for the source article and creates a sitelink
 * to the translated article on the target language Wikipedia.
 *
 * @param string      $sourcetitle   The source MDWiki article title
 * @param string      $lang          The target language code
 * @param string      $user          The username for OAuth
 * @param string      $targettitle   The target Wikipedia article title
 * @param string      $access_key    OAuth access token key
 * @param string      $access_secret OAuth access token secret
 *
 * @return array<string, mixed> The result array containing:
 *                              - result: "success" on success
 *                              - qid: The Wikidata QID
 *                              - error: Error message on failure
 *
 * @example
 * $result = LinkToWikidata("Heart", "ar", "JohnDoe", "قلب", $key, $secret);
 * if (isset($result['result']) && $result['result'] === 'success') {
 *     echo "Linked to QID: {$result['qid']}";
 * }
 */
function LinkToWikidata(
    string $sourcetitle,
    string $lang,
    string $user,
    string $targettitle,
    string $access_key,
    string $access_secret
): array {
    // Get QID for source article
    $qids = GetQidForMdtitle($sourcetitle);
    $qid = $qids[0]['qid'] ?? '';

    // Verify we have valid credentials
    $credentials = getAccessCredentials($user, $access_key, $access_secret);
    if ($credentials === null) {
        return [
            'error' => 'Access credentials not found for user: ' . $user,
            'qid' => $qid
        ];
    }
    [$access_key, $access_secret] = $credentials;

    // Create the sitelink
    $link_result = LinkIt($qid, $lang, $sourcetitle, $targettitle, $access_key, $access_secret) ?? [];

    $link_result["qid"] = $qid;

    if (isset($link_result['success']) && $link_result['success']) {
        pub_test_print("success: true");
        return ['result' => "success", 'qid' => $qid];
    }

    return $link_result;
}

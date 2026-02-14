<?php

declare(strict_types=1);

/**
 * Main entry point orchestration for Wikipedia article publishing.
 *
 * This file contains the core workflow functions that coordinate the
 * publishing process: user validation, text preprocessing, API editing,
 * and result handling.
 *
 * @package Publish
 * @author  MDWiki Team
 * @since   1.0.0
 *
 * @see https://www.mediawiki.org/wiki/API:Edit
 *
 * @example
 * // The start() function is automatically called with $_POST at the end of this file
 * // POST parameters expected:
 * // - user: Wikipedia username
 * // - title: Target article title
 * // - target: Target language code (e.g., 'ar', 'fr')
 * // - sourcetitle: Source article title from MDWiki
 * // - text: Article wikitext content
 * // - campaign: Optional campaign identifier
 * // - revid/revision: Optional revision ID fallback
 */

use function Publish\Helps\pub_test_print;
use function Publish\AccessHelps\get_access_from_db;
use function Publish\AccessHelpsNew\get_access_from_db_new;
use function WpRefs\FixPage\DoChangesToText1;
use function Publish\EditProcess\processEdit;
use function Publish\FilesHelps\to_do;
use function Publish\Revids\get_revid_db;
use function Publish\Revids\get_revid;
use function Publish\AddToDb\InsertPublishReports;

/**
 * Type alias for access credentials array.
 *
 * @typedef AccessCredentials array{access_key: string, access_secret: string}
 */

/**
 * Type alias for request data array.
 *
 * @typedef RequestData array{
 *     user?: string,
 *     title?: string,
 *     target?: string,
 *     sourcetitle?: string,
 *     text?: string,
 *     campaign?: string,
 *     revid?: string|int,
 *     revision?: string|int,
 *     wpCaptchaId?: string,
 *     wpCaptchaWord?: string
 * }
 */

/**
 * Type alias for processing context tab.
 *
 * @typedef ProcessTab array{
 *     title: string,
 *     summary: string,
 *     lang: string,
 *     user: string,
 *     campaign: string,
 *     result: string,
 *     edit: array,
 *     sourcetitle: string,
 *     revid?: string|int,
 *     fix_refs?: string,
 *     result_to_cx?: array
 * }
 */

// Check if the request is a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // Method Not Allowed
    echo json_encode(['error' => 'Only POST requests are allowed']);
    exit;
}

/**
 * Creates an edit summary for Wikipedia article creation.
 *
 * Generates a standardized summary following the pattern:
 * "Created by translating the page [[:mdwiki:Special:Redirect/revision/{revid}|{sourcetitle}]] to:{lang} {hashtag}"
 *
 * @param string|int $revid      The revision ID of the source article
 * @param string     $sourcetitle The source article title
 * @param string     $to         The target language code
 * @param string     $hashtag    The hashtag to append (e.g., "#mdwikicx")
 *
 * @return string The formatted edit summary
 *
 * @example
 * $summary = make_summary(12345, "Heart disease", "ar", "#mdwikicx");
 * // Returns: "Created by translating the page [[:mdwiki:Special:Redirect/revision/12345|Heart disease]] to:ar #mdwikicx"
 */
function make_summary($revid, string $sourcetitle, string $to, string $hashtag): string
{
    $revidStr = (string) $revid;
    return "Created by translating the page [[:mdwiki:Special:Redirect/revision/{$revidStr}|{$sourcetitle}]] to:{$to} {$hashtag}";
}

/**
 * Formats and normalizes an article title.
 *
 * Performs the following transformations:
 * - Replaces underscores with spaces
 * - Normalizes special user path prefixes
 *
 * @param string $title The raw title to format
 *
 * @return string The formatted title
 *
 * @example
 * $title = formatTitle("Heart_disease");
 * // Returns: "Heart disease"
 */
function formatTitle(string $title): string
{
    $title = str_replace("_", " ", $title);
    // Normalize special user path prefix
    $title = str_replace("Mr. Ibrahem 1/", "Mr. Ibrahem/", $title);
    return $title;
}

/**
 * Formats and normalizes a Wikipedia username.
 *
 * Maps special usernames to their canonical forms and
 * replaces underscores with spaces.
 *
 * @param string $user The raw username
 *
 * @return string The formatted username
 *
 * @example
 * $user = formatUser("Mr._Ibrahem_1");
 * // Returns: "Mr. Ibrahem"
 */
function formatUser(string $user): string
{
    $specialUsers = [
        "Mr. Ibrahem 1" => "Mr. Ibrahem",
        "Admin" => "Mr. Ibrahem"
    ];

    $user = $specialUsers[$user] ?? $user;
    return str_replace("_", " ", $user);
}

/**
 * Determines the hashtag to use in edit summaries.
 *
 * Uses "#mdwikicx" for most translations, but omits the hashtag
 * for specific user/title combinations to reduce noise.
 *
 * @param string $title The article title
 * @param string $user  The username
 *
 * @return string The hashtag (or empty string if none)
 *
 * @example
 * $tag = determineHashtag("Some article", "JohnDoe");
 * // Returns: "#mdwikicx"
 *
 * $tag = determineHashtag("User:Mr. Ibrahem/test", "Mr. Ibrahem");
 * // Returns: "" (no hashtag for this combination)
 */
function determineHashtag(string $title, string $user): string
{
    $hashtag = "#mdwikicx";

    // Omit hashtag for specific user's own articles to reduce noise
    if (strpos($title, "Mr. Ibrahem") !== false && $user === "Mr. Ibrahem") {
        $hashtag = "";
    }

    return $hashtag;
}

/**
 * Handles cases where user lacks OAuth access credentials.
 *
 * Logs the access denial, records to database, and returns
 * an error response to the client.
 *
 * @param string     $user The username that was denied access
 * @param ProcessTab $tab  The processing context for logging
 *
 * @return void Outputs JSON error response and exits
 *
 * @example
 * handleNoAccess("UnknownUser", $tab);
 * // Outputs: {"error": {"code": "noaccess", ...}, "username": "UnknownUser"}
 */
function handleNoAccess(string $user, array $tab): void
{
    $error = ['code' => 'noaccess', 'info' => 'noaccess'];

    $editit = [
        'error' => $error,
        'edit' => ['error' => $error, 'username' => $user],
        'username' => $user
    ];

    $tab['result_to_cx'] = $editit;

    to_do($tab, "noaccess");
    InsertPublishReports($tab['title'], $user, $tab['lang'], $tab['sourcetitle'], "noaccess", $tab);

    pub_test_print("\n<br>\n<br>");

    echo json_encode($editit, JSON_PRETTY_PRINT);
}

/**
 * Executes the main publishing workflow with validated credentials.
 *
 * This is the second stage of processing, called after user credentials
 * have been validated. Performs:
 * 1. Revision ID lookup
 * 2. Edit summary generation
 * 3. Text preprocessing via fix_refs
 * 4. Edit execution via Wikipedia API
 *
 * @param RequestData       $request The original POST request data
 * @param string            $user    The validated username
 * @param AccessCredentials $access  The OAuth credentials
 * @param ProcessTab        $tab     The processing context
 *
 * @return void Outputs JSON response from edit operation
 */
function start2(array $request, string $user, array $access, array $tab): void
{
    $text = $request['text'] ?? '';

    // Step 1: Get revision ID for source article
    // First try JSON file, then database API
    $revid = get_revid($tab['sourcetitle']);
    if (empty($revid)) {
        $revid = get_revid_db($tab['sourcetitle']);
    }

    // SECURITY NOTE: Fallback to request parameter without validation
    // @see ANALYSIS_REPORT.md SEC-010
    if (empty($revid)) {
        $tab['empty revid'] = 'Can not get revid from all_pages_revids.json';
        $revid = $request['revid'] ?? $request['revision'] ?? '';
    }

    $tab['revid'] = $revid;

    // Step 2: Generate edit summary
    $hashtag = determineHashtag($tab['title'], $user);
    $tab['summary'] = make_summary($revid, $tab['sourcetitle'], $tab['lang'], $hashtag);

    // Step 3: Preprocess wikitext via fix_refs repository
    // This fixes references, infoboxes, categories, etc.
    $newtext = DoChangesToText1($tab['sourcetitle'], $tab['title'], $text, $tab['lang'], $revid);

    if (!empty($newtext)) {
        $tab['fix_refs'] = ($newtext !== $text) ? 'yes' : 'no';
        $text = $newtext;
    }

    // Step 4: Execute the edit via Wikipedia API
    $editit = processEdit($request, $access, $text, $user, $tab);

    pub_test_print("\n<br>\n<br>");

    echo json_encode($editit, JSON_PRETTY_PRINT);
}

/**
 * Main entry point for article publishing requests.
 *
 * Validates user access, then delegates to start2() for processing.
 * This is the first stage of the publishing workflow.
 *
 * Expected POST parameters:
 * - user: Wikipedia username (required)
 * - title: Target article title (required)
 * - target: Target language code (required)
 * - sourcetitle: Source MDWiki article title (required)
 * - text: Article wikitext (required)
 * - campaign: Campaign identifier (optional)
 * - revid/revision: Revision ID fallback (optional)
 *
 * @param RequestData $request The POST request data (typically $_POST)
 *
 * @return void Outputs JSON response
 *
 * @example
 * // Called automatically at end of file with:
 * start($_POST);
 */
function start(array $request): void
{
    // Normalize and validate user/title inputs
    $user = formatUser($request['user'] ?? '');
    $title = formatTitle($request['title'] ?? '');

    // Initialize processing context
    $tab = [
        'title' => $title,
        'summary' => "",
        'lang' => $request['target'] ?? '',
        'user' => $user,
        'campaign' => $request['campaign'] ?? '',
        'result' => "",
        'edit' => [],
        'sourcetitle' => $request['sourcetitle'] ?? ''
    ];

    // Get OAuth credentials - try new table first, then legacy
    $access = get_access_from_db_new($user);
    if ($access === null) {
        $access = get_access_from_db($user);
    }

    // Route based on credential availability
    if ($access === null) {
        handleNoAccess($user, $tab);
    } else {
        start2($request, $user, $access, $tab);
    }
}

// Execute the main entry point with POST data
start($_POST);

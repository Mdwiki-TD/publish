<?php

declare(strict_types=1);

/**
 * Wikipedia edit processing and result handling.
 *
 * This module handles the core editing workflow including:
 * - Preparing API parameters for Wikipedia edits
 * - Executing edits via OAuth-authenticated API calls
 * - Handling successful edits (Wikidata linking, database recording)
 * - Error classification and logging
 * - Fallback user handling for failed Wikidata operations
 *
 * @package Publish\EditProcess
 * @author  MDWiki Team
 * @since   1.0.0
 *
 * @see https://www.mediawiki.org/wiki/API:Edit
 * @see https://www.wikidata.org/w/api.php?action=help&modules=wbsetsitelink
 *
 * @example
 * use function Publish\EditProcess\processEdit;
 *
 * $result = processEdit($request, $access, $wikitext, $user, $tab);
 * // Returns edit result with LinkToWikidata and sql_result on success
 */

namespace Publish\EditProcess;

use function Publish\Helps\pub_test_print;
use function Publish\DoEdit\publish_do_edit;
use function Publish\AddToDb\InsertPageTarget;
use function Publish\AddToDb\retrieveCampaignCategories;
use function Publish\WD\LinkToWikidata;
use function Publish\FilesHelps\to_do;
use function Publish\AccessHelpsNew\get_access_from_db_new;
use function Publish\AccessHelps\get_access_from_db;
use function Publish\AddToDb\InsertPublishReports;

/**
 * List of main error types that indicate edit failure.
 *
 * @var array<int, string>
 */
const ERRORS_MAIN = [
    "protectedpage",
    "titleblacklist",
    "ratelimited",
    "editconflict",
    "spam filter",
    "abusefilter",
    "mwoauth-invalid-authorization",
];

/**
 * Mapping of Wikidata error patterns to file names for logging.
 *
 * @var array<string, string>
 */
const ERRORS_WD = [
    'Links to user pages' => "wd_user_pages",
    'get_csrftoken' => "wd_csrftoken",
    'protectedpage' => "wd_protectedpage",
];

/**
 * Determines the appropriate error file name based on the error content.
 *
 * Analyzes the edit result to classify the error type for logging purposes.
 * Checks both main edit errors and Wikidata-specific errors.
 *
 * @param array  $editit        The edit result array
 * @param string $place_holder  Default file name if no specific error matches
 *
 * @return string The determined file name for logging
 *
 * @example
 * $file = get_errors_file($editResult, "errors");
 * // Returns specific error type like "protectedpage" or "errors"
 */
function get_errors_file(array $editit, string $place_holder): string
{
    $to_do_file = $place_holder;

    $errs = ($place_holder === "errors") ? ERRORS_MAIN : array_values(ERRORS_WD);

    $c_text = json_encode($editit);

    foreach ($errs as $err) {
        if (strpos($c_text, $err) !== false) {
            $to_do_file = $err;
            break;
        }
    }

    return $to_do_file;
}

/**
 * Retries Wikidata linking with fallback user credentials.
 *
 * When the original user's credentials fail for Wikidata operations,
 * this function attempts to use "Mr. Ibrahem" credentials as a fallback.
 *
 * AUDIT WARNING: This creates an audit trail issue as edits may be
 * attributed to the wrong user.
 * @see ANALYSIS_REPORT.md LOG-002
 *
 * @param string $sourcetitle    The source article title
 * @param string $lang           The target language code
 * @param string $title          The target article title
 * @param string $user           The original username
 * @param string $original_error The error that triggered the fallback
 *
 * @return array The Wikidata linking result, with fallback metadata if successful
 */
function retryWithFallbackUser(
    string $sourcetitle,
    string $lang,
    string $title,
    string $user,
    string $original_error
): array {
    $LinkTowd = [];
    pub_test_print("get_csrftoken failed for user: $user, retrying with Mr. Ibrahem");

    // Retry with "Mr. Ibrahem" credentials - get fresh credentials from database
    $fallback_access = get_access_from_db_new('Mr. Ibrahem');
    if ($fallback_access === null) {
        $fallback_access = get_access_from_db('Mr. Ibrahem');
    }

    if ($fallback_access !== null) {
        $fallback_access_key = $fallback_access['access_key'];
        $fallback_access_secret = $fallback_access['access_secret'];

        $LinkTowd = LinkToWikidata(
            $sourcetitle,
            $lang,
            'Mr. Ibrahem',
            $title,
            $fallback_access_key,
            $fallback_access_secret
        ) ?? [];

        // Add a note that fallback was used
        if (!isset($LinkTowd['error'])) {
            $LinkTowd['fallback_user'] = 'Mr. Ibrahem';
            $LinkTowd['original_user'] = $user;
            pub_test_print("Successfully linked using Mr. Ibrahem fallback credentials");
        }
    }

    return $LinkTowd;
}

/**
 * Handles post-edit operations after a successful Wikipedia edit.
 *
 * Performs Wikidata sitelink operation and records the translation
 * in the database. Implements fallback to alternative credentials
 * if the primary user's Wikidata access fails.
 *
 * @param string $sourcetitle   The source article title
 * @param string $lang          The target language code
 * @param string $user          The username
 * @param string $title         The target article title
 * @param string $access_key    OAuth access key
 * @param string $access_secret OAuth access secret
 *
 * @return array The Wikidata linking result, possibly with fallback information
 *
 * @example
 * $result = handleSuccessfulEdit("Heart", "ar", "John", "قلب", $key, $secret);
 * // Links "قلب" article to Wikidata and returns result
 */
function handleSuccessfulEdit(
    string $sourcetitle,
    string $lang,
    string $user,
    string $title,
    string $access_key,
    string $access_secret
): array {
    $LinkTowd = [];

    try {
        $LinkTowd = LinkToWikidata($sourcetitle, $lang, $user, $title, $access_key, $access_secret) ?? [];

        // Check if the error is get_csrftoken failure and user is not already "Mr. Ibrahem"
        // AUDIT WARNING: Fallback creates attribution issues
        // @see ANALYSIS_REPORT.md LOG-002
        if (
            isset($LinkTowd['error']) &&
            $LinkTowd['error'] === 'get_csrftoken failed' &&
            $user !== 'Mr. Ibrahem'
        ) {
            $LinkTowd['fallback'] = retryWithFallbackUser(
                $sourcetitle,
                $lang,
                $title,
                $user,
                $LinkTowd['error']
            );
        }
    } catch (\Exception $e) {
        pub_test_print($e->getMessage());
    }

    // Log Wikidata errors
    if (isset($LinkTowd['error'])) {
        $tab3 = [
            'error' => $LinkTowd['error'],
            'qid' => $LinkTowd['qid'] ?? "",
            'title' => $title,
            'sourcetitle' => $sourcetitle,
            'fallback' => $LinkTowd['fallback'] ?? "",
            'lang' => $lang,
            'username' => $user
        ];

        $file_name = get_errors_file($LinkTowd, "wd_errors");

        to_do($tab3, $file_name);
        InsertPublishReports($title, $user, $lang, $sourcetitle, $file_name, $tab3);
    }

    return $LinkTowd;
}

/**
 * Prepares API parameters for a Wikipedia edit request.
 *
 * Constructs the parameter array for the MediaWiki API edit action,
 * including optional CAPTCHA parameters if provided.
 *
 * @param string $title   The target article title
 * @param string $summary The edit summary
 * @param string $text    The article wikitext
 * @param array  $request The original request (may contain CAPTCHA data)
 *
 * @return array<string, string> The API parameters array
 *
 * @example
 * $params = prepareApiParams("قلب", "Created article", $wikitext, $_POST);
 * // Returns: ['action' => 'edit', 'title' => 'قلب', 'summary' => ..., 'text' => ..., 'format' => 'json']
 */
function prepareApiParams(string $title, string $summary, string $text, array $request): array
{
    $apiParams = [
        'action' => 'edit',
        'title' => $title,
        'summary' => $summary,
        'text' => $text,
        'format' => 'json',
    ];

    // Include CAPTCHA response if provided
    if (isset($request['wpCaptchaId']) && isset($request['wpCaptchaWord'])) {
        $apiParams['wpCaptchaId'] = $request['wpCaptchaId'];
        $apiParams['wpCaptchaWord'] = $request['wpCaptchaWord'];
    }

    return $apiParams;
}

/**
 * Records the translation in the database after successful edit.
 *
 * Looks up campaign category mapping and inserts the translation
 * record into the appropriate table (pages or pages_users).
 *
 * @param string $title         The target article title
 * @param string $lang          The target language code
 * @param string $user          The username
 * @param array  $wd_result     The Wikidata linking result
 * @param string $campaign      The campaign identifier
 * @param string $sourcetitle   The source article title
 * @param string $mdwiki_revid  The MDWiki revision ID
 *
 * @return array Result metadata including table selection info
 */
function add_to_db(
    string $title,
    string $lang,
    string $user,
    array $wd_result,
    string $campaign,
    string $sourcetitle,
    string $mdwiki_revid
): array {
    $camp_to_cat = retrieveCampaignCategories();
    $cat = $camp_to_cat[$campaign] ?? '';
    $to_users_table = false;

    // Check for abuse filter warning that indicates user page edit
    if (strpos(json_encode($wd_result), "abusefilter-warning-39") !== false) {
        $to_users_table = true;
    }

    $is_user_page = InsertPageTarget(
        $sourcetitle,
        'lead',
        $cat,
        $lang,
        $user,
        "",
        $title,
        $to_users_table,
        $mdwiki_revid
    );

    return $is_user_page;
}

/**
 * Main edit processing function.
 *
 * Orchestrates the complete edit workflow:
 * 1. Prepares API parameters
 * 2. Executes the edit via Wikipedia API
 * 3. Classifies the result (success, captcha, error)
 * 4. Handles successful edits (Wikidata linking, database recording)
 * 5. Logs results to files and database
 *
 * @param array              $request The original POST request
 * @param AccessCredentials  $access  OAuth credentials array
 * @param string             $text    The article wikitext
 * @param string             $user    The username
 * @param ProcessTab         $tab     Processing context
 *
 * @return array The edit result, including LinkToWikidata and sql_result on success
 *
 * @example
 * $result = processEdit($_POST, $access, $wikitext, "JohnDoe", $tab);
 * if (($result['edit']['result'] ?? '') === 'Success') {
 *     echo "Article published successfully!";
 * }
 */
function processEdit(array $request, array $access, string $text, string $user, array $tab): array
{
    $sourcetitle = $tab['sourcetitle'];
    $lang = $tab['lang'];
    $campaign = $tab['campaign'];
    $title = $tab['title'];
    $summary = $tab['summary'];
    $mdwiki_revid = (string) ($tab['revid'] ?? "");

    $apiParams = prepareApiParams($title, $summary, $text, $request);

    $access_key = $access['access_key'];
    $access_secret = $access['access_secret'];

    $apiParams["text"] = $text;

    // Execute the edit via Wikipedia API
    $editit = publish_do_edit($apiParams, $lang, $access_key, $access_secret);

    $Success = $editit['edit']['result'] ?? '';
    $is_captcha = $editit['edit']['captcha'] ?? null;

    $tab['result'] = $Success;

    $to_do_file = "";

    if ($Success === 'Success') {
        // Handle successful edit: Wikidata linking and database recording
        $editit['LinkToWikidata'] = handleSuccessfulEdit(
            $sourcetitle,
            $lang,
            $user,
            $title,
            $access_key,
            $access_secret
        );

        $editit['sql_result'] = add_to_db(
            $title,
            $lang,
            $user,
            $editit['LinkToWikidata'],
            $campaign,
            $sourcetitle,
            $mdwiki_revid
        );

        $to_do_file = "success";
    } elseif ($is_captcha) {
        // CAPTCHA challenge required
        $to_do_file = "captcha";
    } else {
        // Other error - classify for logging
        $to_do_file = get_errors_file($editit, "errors");
    }

    $tab['result_to_cx'] = $editit;

    to_do($tab, $to_do_file);
    InsertPublishReports($title, $user, $lang, $sourcetitle, $to_do_file, $tab);

    return $editit;
}

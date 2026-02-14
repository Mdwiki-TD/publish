<?php

declare(strict_types=1);

/**
 * Database recording for translation tracking.
 *
 * This module handles recording translation activities to the database,
 * including the pages and pages_users tables for tracking completed
 * translations and the publish_reports table for logging all operations.
 *
 * @package Publish\AddToDb
 * @author  MDWiki Team
 * @since   1.0.0
 *
 * @example
 * use function Publish\AddToDb\InsertPageTarget;
 * use function Publish\AddToDb\InsertPublishReports;
 *
 * InsertPageTarget("Heart", "lead", "medicine", "ar", "John", "", "قلب", false, "12345");
 * InsertPublishReports("قلب", "John", "ar", "Heart", "success", $data);
 */

namespace Publish\AddToDb;

include_once __DIR__ . '/../include.php';

use function Publish\MdwikiSql\fetch_query;
use function Publish\MdwikiSql\execute_query;

/**
 * Path to the words count JSON file.
 *
 * @var string
 */
$word_file = __DIR__ . "/../../td/Tables/jsons/words.json";
if (!file_exists($word_file)) {
    $word_file = "I:/mdwiki/mdwiki/public_html/td/Tables/jsons/words.json";
}

/**
 * Cached word counts for articles.
 *
 * @var array<string, int>
 */
$Words_table = [];

try {
    $file = file_get_contents($word_file);
    $Words_table = json_decode($file, true);
} catch (\Exception $e) {
    $Words_table = [];
}

/**
 * Retrieves the mapping of campaigns to categories.
 *
 * Queries the categories table to get the mapping between campaign
 * identifiers and their corresponding tracking categories.
 *
 * @return array<string, string> Mapping of campaign ID to category name
 *
 * @example
 * $mapping = retrieveCampaignCategories();
 * $category = $mapping['WPMC'] ?? ''; // e.g., "Wikipedia:WikiProject Medicine"
 */
function retrieveCampaignCategories(): array
{
    $camp_to_cats = [];
    $results = fetch_query('select id, category, category2, campaign, depth, def from categories;');

    foreach ($results as $tab) {
        $camp_to_cats[$tab['campaign']] = $tab['category'];
    }

    return $camp_to_cats;
}

/**
 * Checks if a translation record already exists.
 *
 * Searches both the pages and pages_users tables for an existing
 * record of this title/language/user combination with a target.
 *
 * @param string $title        The source title
 * @param string $lang         The target language code
 * @param string $user         The username
 * @param string $target       The target article title
 * @param bool   $use_user_sql Whether to check the pages_users table
 *
 * @return bool True if a record exists
 */
function find_exists(string $title, string $lang, string $user, string $target, bool $use_user_sql): bool
{
    $query = <<<SQL
        SELECT 1 FROM (
            SELECT 1 FROM pages WHERE title = ? AND lang = ? AND user = ? AND target != ""
            UNION
            SELECT 1 FROM pages_users WHERE title = ? AND lang = ? AND user = ? AND target != ""
        ) AS combined
    SQL;

    $params = [$title, $lang, $user, $title, $lang, $user];

    $result = fetch_query($query, $params);

    return count($result) > 0;
}

/**
 * Finds an existing record or updates its target if found.
 *
 * Searches for an existing record and updates the target field
 * if it's currently empty. Used for updating draft translations.
 *
 * SECURITY WARNING: Table name interpolation without validation.
 * @see ANALYSIS_REPORT.md SEC-002
 *
 * @param string $title        The source title
 * @param string $lang         The target language code
 * @param string $user         The username
 * @param string $target       The target article title
 * @param bool   $use_user_sql Which table to use (pages or pages_users)
 *
 * @return bool True if a record was found
 */
function find_exists_or_update(string $title, string $lang, string $user, string $target, bool $use_user_sql): bool
{
    $table_name = $use_user_sql ? 'pages_users' : 'pages';

    $query = <<<SQL
        SELECT * FROM $table_name WHERE title = ? AND lang = ? AND user = ?
    SQL;

    $result = fetch_query($query, [$title, $lang, $user]);

    if (count($result) > 0) {
        $query = <<<SQL
            UPDATE $table_name SET target = ?, pupdate = DATE(NOW())
            WHERE title = ? AND lang = ? AND user = ? AND (target = "" OR target IS NULL)
        SQL;

        $params = [$target, $title, $lang, $user];

        execute_query($query, $params, $table_name);
    }

    return count($result) > 0;
}

/**
 * Inserts a new translation record into the database.
 *
 * Records the translation in either the pages or pages_users table,
 * depending on whether it's a user page translation. Also looks up
 * the word count for statistics tracking.
 *
 * @param string $title          The source article title
 * @param string $tr_type        The translation type (e.g., 'lead')
 * @param string $cat            The tracking category
 * @param string $lang           The target language code
 * @param string $user           The username
 * @param string $test           Test mode flag (unused)
 * @param string $target         The target article title
 * @param bool   $to_users_table Whether to use pages_users table
 * @param string $mdwiki_revid   The MDWiki revision ID
 *
 * @return array<string, mixed> Result metadata including table selection info
 *
 * @example
 * $result = InsertPageTarget("Heart", "lead", "medicine", "ar", "John", "", "قلب", false, "12345");
 * // Records the translation in the pages table
 */
function InsertPageTarget(
    string $title,
    string $tr_type,
    string $cat,
    string $lang,
    string $user,
    string $test,
    string $target,
    bool $to_users_table,
    string $mdwiki_revid
): array {
    global $Words_table;

    $use_user_sql = false;

    $title = str_replace("_", " ", $title);
    $target = str_replace("_", " ", $target);
    $user = str_replace("_", " ", $user);

    $tab = [
        'use_user_sql' => $use_user_sql,
        'to_users_table' => $to_users_table,
    ];

    if (empty($user) || empty($title) || empty($lang)) {
        $tab['one_empty'] = ['title' => $title, 'lang' => $lang, 'user' => $user];
        return $tab;
    }

    $word = $Words_table[$title] ?? 0;

    if ($to_users_table) {
        $tab['use_user_sql'] = $to_users_table;
    } else {
        $user_t = str_replace(["User:", "user:"], "", $user);

        // Check if target contains username (indicates user page translation)
        if (strpos($target, $user_t) !== false) {
            $tab['use_user_sql'] = true;
        }
    }

    $exists = find_exists_or_update($title, $lang, $user, $target, $tab['use_user_sql']);

    if ($exists) {
        $tab['exists'] = "already_in";
        return $tab;
    }

    $table_name = $tab['use_user_sql'] ? 'pages_users' : 'pages';

    $query = <<<SQL
        INSERT INTO $table_name (title, word, translate_type, cat, lang, user, pupdate, target, mdwiki_revid)
        SELECT ?, ?, ?, ?, ?, ?, DATE(NOW()), ?, ?
    SQL;

    $params = [
        $title,
        $word,
        $tr_type,
        $cat,
        $lang,
        $user,
        $target,
        $mdwiki_revid
    ];

    if (!empty($test)) {
        echo "<br>{$query}<br>";
    }

    execute_query($query, $params, $table_name);

    $tab['execute_query'] = true;

    return $tab;
}

/**
 * Records a publish operation to the publish_reports table.
 *
 * Logs all publish attempts (successful or failed) with full context
 * for auditing and debugging purposes.
 *
 * @param string              $title       The target article title
 * @param string              $user        The username
 * @param string              $lang        The target language code
 * @param string              $sourcetitle The source article title
 * @param string              $result      The result type (e.g., 'success', 'error')
 * @param array<string, mixed> $data       The full context data
 *
 * @return void
 *
 * @example
 * InsertPublishReports("قلب", "John", "ar", "Heart", "success", $contextData);
 */
function InsertPublishReports(
    string $title,
    string $user,
    string $lang,
    string $sourcetitle,
    string $result,
    array $data
): void {
    $query = "INSERT INTO publish_reports (`date`, `title`, `user`, `lang`, `sourcetitle`, `result`, `data`) " .
             "VALUES (NOW(), ?, ?, ?, ?, ?, ?)";

    $report_data = json_encode($data);

    // Remove .json suffix from result if present
    $result = str_replace(".json", "", $result);

    $params = [$title, $user, $lang, $sourcetitle, $result, $report_data];

    execute_query($query, $params, "publish_reports");
}

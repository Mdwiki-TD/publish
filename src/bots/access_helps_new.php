<?php

declare(strict_types=1);

/**
 * New OAuth access token management with improved storage.
 *
 * This module provides functions to retrieve and delete OAuth access
 * tokens from the new keys_new table. The username is stored encrypted,
 * requiring a full table scan and decryption for lookup.
 *
 * PERFORMANCE WARNING: The user lookup decrypts all usernames to find a match.
 * @see ANALYSIS_REPORT.md LOG-001, PERF-002
 *
 * @package Publish\AccessHelpsNew
 * @author  MDWiki Team
 * @since   1.0.0
 *
 * @see access_helps.php for the legacy access_keys table implementation
 *
 * @example
 * use function Publish\AccessHelpsNew\get_access_from_db_new;
 *
 * $access = get_access_from_db_new('JohnDoe');
 * if ($access !== null) {
 *     // Use $access['access_key'] and $access['access_secret']
 * }
 */

namespace Publish\AccessHelpsNew;

include_once __DIR__ . '/../include.php';

use function Publish\MdwikiSql\execute_query;
use function Publish\MdwikiSql\fetch_query;
use function Publish\Helps\decode_value;

/**
 * Cache for user ID lookups to avoid repeated table scans.
 *
 * @var array<string, int|null>
 */
$user_ids_cache = [];

/**
 * Looks up the numeric user ID for a username.
 *
 * PERFORMANCE ISSUE: This function loads ALL users from the database
 * and decrypts each username to find a match. This is O(n) with the
 * number of users and involves expensive decryption operations.
 *
 * RECOMMENDATION: Add an indexed username_hash column for O(1) lookup.
 * @see ANALYSIS_REPORT.md LOG-001, PERF-002
 *
 * @param string $user The username to look up
 *
 * @return int|string|null The user ID or null if not found
 *
 * @example
 * $userId = get_user_id('JohnDoe');
 * if ($userId !== null) {
 *     echo "User ID: $userId";
 * }
 */
function get_user_id(string $user)
{
    global $user_ids_cache;

    $user = trim($user);

    // Check cache first
    if (isset($user_ids_cache[$user])) {
        return $user_ids_cache[$user];
    }

    // PERFORMANCE WARNING: Full table scan with decryption
    // @see ANALYSIS_REPORT.md LOG-001, PERF-002
    $query = "SELECT id, u_n FROM keys_new";

    $result = fetch_query($query);

    if (!$result) {
        return null;
    }

    // Decrypt each username to find a match
    foreach ($result as $row) {
        $user_id = $row['id'];
        $user_db = decode_value($row['u_n'], 'decrypt');

        if ($user_db === $user) {
            $user_ids_cache[$user] = $user_id;
            return $user_id;
        }
    }

    return null;
}

/**
 * Retrieves OAuth access credentials for a user from the new table.
 *
 * Looks up the user's numeric ID first, then retrieves the encrypted
 * OAuth tokens from the keys_new table.
 *
 * @param string $user The username to look up
 *
 * @return array{access_key: string, access_secret: string}|null The credentials or null if not found
 *
 * @example
 * $credentials = get_access_from_db_new('JohnDoe');
 * if ($credentials !== null) {
 *     echo "Access key: " . $credentials['access_key'];
 * }
 */
function get_access_from_db_new(string $user): ?array
{
    $user = trim($user);

    $query = <<<SQL
        SELECT a_k, a_s
        FROM keys_new
        WHERE id = ?
    SQL;

    $user_id = get_user_id($user);

    if (!$user_id) {
        return null;
    }

    $result = fetch_query($query, [$user_id]);

    if (!$result) {
        return null;
    }

    $result = $result[0];

    return [
        'access_key' => decode_value($result['a_k'], "decrypt"),
        'access_secret' => decode_value($result['a_s'], "decrypt")
    ];
}

/**
 * Deletes OAuth access credentials for a user from the new table.
 *
 * Looks up the user's numeric ID first, then deletes their record
 * from the keys_new table.
 *
 * @param string $user The username whose credentials to delete
 *
 * @return void
 *
 * @example
 * del_access_from_db_new('JohnDoe');
 * // User must re-authenticate to get new tokens
 */
function del_access_from_db_new(string $user): void
{
    $user = trim($user);

    $query = <<<SQL
        DELETE FROM keys_new WHERE id = ?
    SQL;

    $user_id = get_user_id($user);

    if (!$user_id) {
        return;
    }

    execute_query($query, [$user_id], "keys_new");
}

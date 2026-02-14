<?php

declare(strict_types=1);

/**
 * Legacy OAuth access token management.
 *
 * This module provides functions to retrieve and delete OAuth access
 * tokens from the legacy access_keys table. These tokens are stored
 * encrypted and must be decrypted before use.
 *
 * @package Publish\AccessHelps
 * @author  MDWiki Team
 * @since   1.0.0
 *
 * @see access_helps_new.php for the new keys_new table implementation
 *
 * @example
 * use function Publish\AccessHelps\get_access_from_db;
 * use function Publish\AccessHelps\del_access_from_db;
 *
 * $access = get_access_from_db('JohnDoe');
 * if ($access !== null) {
 *     // Use $access['access_key'] and $access['access_secret']
 * }
 */

namespace Publish\AccessHelps;

include_once __DIR__ . '/../include.php';

use function Publish\MdwikiSql\execute_query;
use function Publish\MdwikiSql\fetch_query;
use function Publish\Helps\encode_value;
use function Publish\Helps\decode_value;

/**
 * Retrieves OAuth access credentials for a user from the legacy table.
 *
 * Looks up the user's OAuth access token and secret from the access_keys
 * table. The tokens are stored encrypted and are decrypted before being
 * returned.
 *
 * @param string $user The username to look up
 *
 * @return array{access_key: string, access_secret: string}|null The credentials or null if not found
 *
 * @example
 * $credentials = get_access_from_db('JohnDoe');
 * if ($credentials !== null) {
 *     echo "Access key: " . $credentials['access_key'];
 * }
 */
function get_access_from_db(string $user): ?array
{
    $user = trim($user);

    $query = <<<SQL
        SELECT access_key, access_secret
        FROM access_keys
        WHERE user_name = ?;
    SQL;

    $result = fetch_query($query, [$user]);

    if ($result) {
        $result = $result[0];
        return [
            'access_key' => decode_value($result['access_key']),
            'access_secret' => decode_value($result['access_secret'])
        ];
    }

    return null;
}

/**
 * Deletes OAuth access credentials for a user from the legacy table.
 *
 * Removes the user's OAuth tokens from the access_keys table.
 * This is typically used when a user revokes access or needs to
 * re-authenticate.
 *
 * @param string $user The username whose credentials to delete
 *
 * @return void
 *
 * @example
 * del_access_from_db('JohnDoe');
 * // User must re-authenticate to get new tokens
 */
function del_access_from_db(string $user): void
{
    $user = trim($user);

    $query = <<<SQL
        DELETE FROM access_keys WHERE user_name = ?;
    SQL;

    execute_query($query, [$user], "access_keys");
}

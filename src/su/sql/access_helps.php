<?php

namespace Publish\AccessHelps;
/*

Usage:
use function Publish\AccessHelps\get_user_access;
use function Publish\AccessHelps\delete_user_access;

*/

use function Publish\MdwikiSql\execute_query;
use function Publish\MdwikiSql\fetch_query;
use function Publish\CryptHelps\decode_value;

function get_user_id($user)
{
    static $user_ids_cache = [];
    // Validate and sanitize username
    $user = trim($user);
    if (isset($user_ids_cache[$user])) {
        return $user_ids_cache[$user];
    }
    $query = "SELECT id, u_n FROM keys_new";

    $result = fetch_query($query);

    if (!$result) {
        return null;
    }
    foreach ($result as $row) {
        $user_id = $row['id'];
        $user_db = decode_value($row['u_n'], 'decrypt');
        if ($user_db == $user) {
            $user_ids_cache[$user] = $user_id;
            return $user_id;
        }
    }
    return null;
};

function get_access_from_db($user)
{
    $user = trim($user);

    $query = <<<SQL
        SELECT access_key, access_secret
        FROM access_keys
        WHERE user_name = ?;
    SQL;

    $result = fetch_query($query, [$user]);

    if ($result) {
        return [
            'access_key' => decode_value($result[0]['access_key']),
            'access_secret' => decode_value($result[0]['access_secret'])
        ];
    }
    return [];
}

function del_access_from_db($user)
{
    $user = trim($user);

    $query = <<<SQL
        DELETE FROM access_keys WHERE user_name = ?;
    SQL;

    execute_query($query, [$user], "access_keys");
}

function get_access_from_db_new($user)
{
    // Validate and sanitize username
    $user_id = get_user_id(trim($user));
    if (!$user_id) return [];

    // Query to get access_key and access_secret for the user
    $query = <<<SQL
        SELECT a_k, a_s
        FROM keys_new
        WHERE id = ?
    SQL;

    $result = fetch_query($query, [$user_id]);

    if (!$result) return [];

    return [
        'access_key' => decode_value($result[0]['a_k'], "decrypt"),
        'access_secret' => decode_value($result[0]['a_s'], "decrypt")
    ];
}

function del_access_from_db_new($user)
{
    $user_id = get_user_id(trim($user));
    if (!$user_id) return [];

    $query = <<<SQL
        DELETE FROM keys_new WHERE id = ?
    SQL;

    execute_query($query, [$user_id], "keys_new");
}

function get_user_access($user)
{
    $access = get_access_from_db_new($user);
    if (empty($access)) {
        $access = get_access_from_db($user);
    }
    return $access;
}

function delete_user_access($user)
{
    del_access_from_db_new($user);
    del_access_from_db($user);
}

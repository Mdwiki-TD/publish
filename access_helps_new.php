<?php

namespace Publish\AccessHelpsNew;
/*
Usage:
use function Publish\AccessHelpsNew\get_access_from_db_new;
use function Publish\AccessHelpsNew\del_access_from_db_new;
*/

include_once __DIR__ . '/include.php';

use function Publish\MdwikiSql\execute_query;
use function Publish\MdwikiSql\fetch_query;
use function Publish\Helps\encode_value;
use function Publish\Helps\decode_value;

function get_user_id($user)
{
    //---
    // Validate and sanitize username
    $user = trim($user);
    $query = "SELECT id, u_n FROM keys_new";

    $result = fetch_query($query);

    if (!$result) {
        return null;
    }
    // ---
    foreach ($result as $row) {
        $user_id = $row['id'];
        $user_db = decode_value($row['u_n'], 'decrypt');
        if ($user_db == $user) {
            return $user_id;
        }
    }
    // ---
    return null;
};

function get_access_from_db_new($user)
{
    // Validate and sanitize username
    $user = trim($user);

    // Query to get access_key and access_secret for the user
    $query = <<<SQL
        SELECT a_k, a_s
        FROM keys_new
        WHERE id = ?
    SQL;

    $user_id = get_user_id($user);
    //---
    if (!$user_id) {
        return null;
    }

    // تنفيذ الاستعلام وتمرير اسم المستخدم كمعامل
    $result = fetch_query($query, [$user_id]);

    // التحقق مما إذا كان قد تم العثور على نتائج

    if (!$result) {
        return null;
    }

    $result = $result[0];
    // ---
    return [
        'access_key' => decode_value($result['a_k'], $key_type = "decrypt"),
        'access_secret' => decode_value($result['a_s'], $key_type = "decrypt")
    ];
}

function del_access_from_db_new($user)
{
    $user = trim($user);

    $query = <<<SQL
        DELETE FROM keys_new WHERE id = ?
    SQL;

    $user_id = get_user_id($user);
    //---
    if (!$user_id) {
        return null;
    }
    //---
    execute_query($query, [$user_id]);
}

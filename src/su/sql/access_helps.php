<?php

namespace Publish\AccessHelps;
/*

Usage:
use function Publish\AccessHelps\get_access_from_db;
use function Publish\AccessHelps\del_access_from_db;

*/

use function Publish\MdwikiSql\execute_query;
use function Publish\MdwikiSql\fetch_query;
use function Publish\CryptHelps\decode_value;

function get_access_from_db($user)
{
    $user = trim($user);

    $query = <<<SQL
        SELECT access_key, access_secret
        FROM access_keys
        WHERE user_name = ? or user_name_hash = ?;
    SQL;

    $result = fetch_query($query, [$user, hash('sha256', $user)]);

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
        DELETE FROM access_keys WHERE user_name = ? or user_name_hash = ?;
    SQL;

    execute_query($query, [$user, hash('sha256', $user)], "access_keys");
}

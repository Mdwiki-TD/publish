<?php

namespace Publish\AccessHelps;

use Publish\Settings;
use function Publish\MdwikiSql\execute_query;
use function Publish\MdwikiSql\fetch_query;

function get_access_from_db(string $user): array
{
    $user = trim($user);

    $query = <<<SQL
        SELECT access_key, access_secret
        FROM access_keys
        WHERE user_name_hash = ?;
    SQL;

    $result = fetch_query($query, [hash('sha256', $user)]);

    $settings = Settings::getInstance();

    if ($result) {
        $cryptKey = $settings->getKey('crypt');
        return [
            'access_key' => $settings->decodeValue($result[0]['access_key'], $cryptKey),
            'access_secret' => $settings->decodeValue($result[0]['access_secret'], $cryptKey)
        ];
    }
    return [];
}

function del_access_from_db(string $user): bool
{
    $user = trim($user);

    $query = <<<SQL
        DELETE FROM access_keys WHERE user_name_hash = ?;
    SQL;

    return execute_query($query, [hash('sha256', $user)]);
}

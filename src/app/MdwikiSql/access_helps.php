<?php
namespace Publish\AccessHelps;

use Publish\MdwikiSql\Database;
use Publish\Settings;

function get_access_from_db(string $user): array
{
    $user = trim($user);

    $query = <<<SQL
        SELECT access_key, access_secret
        FROM access_keys
        WHERE user_name_hash = ?;
    SQL;

    $db = new Database();

    $result   = $db->fetchQuery($query, [hash('sha256', $user)]);
    $settings = Settings::getInstance();

    if ($result) {
        $cryptKey = $settings->getKey('crypt');
        return [
            'access_key'    => $settings->decodeValue($result[0]['access_key'], $cryptKey),
            'access_secret' => $settings->decodeValue($result[0]['access_secret'], $cryptKey),
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

    $db = new Database();
    return $db->executeQuery($query, [hash('sha256', $user)]);
}

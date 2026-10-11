<?php
namespace Publish\MdwikiSql;

use Publish\MdwikiSql\Database;
use Publish\Settings;

class AccessHelps
{
    public static function loadTitleData(string $title): array
    {
        $title = trim($title);

        $query = <<<SQL
            SELECT
                w.w_lead_words,
                w.w_all_words,
                mr.revid
            FROM
                words w
            LEFT JOIN mdwiki_revids mr ON mr.title = w.w_title
            WHERE
                w.w_title = ?
        SQL;

        $db = new Database();
        return $db->fetchQuery($query, [$title]);
    }

    public static function getAccess(string $user): array
    {
        $user = trim($user);

        $query = <<<SQL
            SELECT access_key, access_secret
            FROM access_keys
            WHERE user_name_hash = ?;
        SQL;

        $db = new Database();

        $result = $db->fetchQuery($query, [hash('sha256', $user)]);
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

    public static function delAccess(string $user): bool
    {
        $user = trim($user);

        $query = <<<SQL
            DELETE FROM access_keys WHERE user_name_hash = ?;
        SQL;

        $db = new Database();
        return $db->executeQuery($query, [hash('sha256', $user)]);
    }
}

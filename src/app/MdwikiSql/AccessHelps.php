<?php
namespace Publish\MdwikiSql;

use Publish\MdwikiSql\Database;
use Publish\Settings;

class AccessHelps
{
    private static ?self $instance = null;

    public function __construct(
        private Database $db = new Database(),
    ) {
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }
    public function loadTitleData(string $title): array
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

        return $this->db->fetchQuery($query, [$title]);
    }

    public function getAccess(string $user): array
    {
        $user = trim($user);

        $query = <<<SQL
            SELECT access_key, access_secret
            FROM access_keys
            WHERE user_name_hash = ?;
        SQL;


        $result = $this->db->fetchQuery($query, [hash('sha256', $user)]);
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

    public function delAccess(string $user): bool
    {
        $user = trim($user);

        $query = <<<SQL
            DELETE FROM access_keys WHERE user_name_hash = ?;
        SQL;

        return $this->db->executeQuery($query, [hash('sha256', $user)]);
    }
}

<?php
namespace Publish\Process;

use Publish\AddToDb\PublishReportsRepository;
use Publish\MdwikiSql\Database;

class EditProcessLog
{
    private const ALLOWED_TABLES = ['pages', 'pages_users'];

    public function findExistsOrUpdate(string $title, string $lang, string $user, string $target, string $tableName): bool
    {
        if (! in_array($tableName, self::ALLOWED_TABLES, true)) {
            error_log("findExistsOrUpdate: Invalid table name: $tableName");
            return false;
        }

        $db     = new Database();
        $result = $db->fetchQuery($query, [$langCode]);
        $query  = <<<SQL
            SELECT * FROM $tableName WHERE title = ? AND lang = ? AND user = ?
        SQL;

        $result = $db->fetchQuery($query, [$title, $lang, $user]);

        if (count($result) > 0) {
            $updateQuery = <<<SQL
                UPDATE $tableName SET target = ?, pupdate = DATE(NOW())
                WHERE title = ? AND lang = ? AND user = ? AND (target = "" OR target IS NULL)
            SQL;
            $db->executeQuery($updateQuery, [$target, $title, $lang, $user]);
        }

        return count($result) > 0;
    }

    public function retrieveCampaignCategories(): array
    {
        $campToCats = [];
        foreach ($db->fetchQuery('SELECT category, campaign FROM categories;') as $k => $tab) {
            $campToCats[$tab['campaign']] = $tab['category'];
        }
        return $campToCats;
    }

    public function getUseUserSql(string $user, string $target, bool $toUsersTable): bool
    {
        if ($toUsersTable) {
            return true;
        }

        $userT = str_replace(["User:", "user:"], "", $user);

        // if target contains user
        return strpos($target, $userT) !== false;
    }

    public function addToDb(
        string $target,
        string $lang,
        string $user,
        bool $toUsersTable,
        string $campaign,
        string $sourcetitle,
        string $mdwikiRevid,
        string $words,
        string $trType
    ): array {
        $sourcetitle = str_replace("_", " ", $sourcetitle);
        $target      = str_replace("_", " ", $target);
        $user        = str_replace("_", " ", $user);

        if (empty($user) || empty($sourcetitle) || empty($lang)) {
            return [
                'use_user_sql'   => false,
                'to_users_table' => $toUsersTable,
                'one_empty'      => ['title' => $sourcetitle, 'lang' => $lang, 'user' => $user],
            ];
        }

        $campToCat = $this->retrieveCampaignCategories();
        $cat       = $campToCat[$campaign] ?? '';

        $useUserSql = $this->getUseUserSql($user, $target, $toUsersTable);
        $tableName  = $useUserSql ? 'pages_users' : 'pages';

        $exists = $this->findExistsOrUpdate($sourcetitle, $lang, $user, $target, $tableName);

        if ($exists) {
            return [
                'use_user_sql'   => $useUserSql,
                'to_users_table' => $toUsersTable,
                'exists'         => "already_in",
            ];
        }

        $repository = new PublishReportsRepository();
        $repository->insertPageTarget(
            $sourcetitle,
            $trType,
            $cat,
            $lang,
            $user,
            $target,
            $tableName,
            $mdwikiRevid,
            $words
        );

        return [
            'use_user_sql'   => $useUserSql,
            'to_users_table' => $toUsersTable,
            'execute_query'  => true,
        ];
    }
}

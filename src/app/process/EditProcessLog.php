<?php

namespace Publish\Process\EditProcessLog;

use Publish\AddToDb\PublishReportsRepository;

use function Publish\MdwikiSql\fetch_query;
use function Publish\MdwikiSql\execute_query;

function find_exists_or_update($title, $lang, $user, $target, $tableName)
{
    $allowedTables = ['pages', 'pages_users']; // Add all valid table names
    if (!in_array($tableName, $allowedTables, true)) {
        error_log("find_exists_or_update: Invalid table name: $tableName");
        return 0;
    }

    $query = <<<SQL
        SELECT * FROM $tableName WHERE title = ? AND lang = ? AND user = ?
    SQL;

    $result = fetch_query($query, [$title, $lang, $user]);

    if (count($result) > 0) {
        $updateQuery = <<<SQL
            UPDATE $tableName SET target = ?, pupdate = DATE(NOW())
            WHERE title = ? AND lang = ? AND user = ? AND (target = "" OR target IS NULL)
        SQL;
        $params = [$target, $title, $lang, $user];
        execute_query($updateQuery, $params);
    }
    return count($result) > 0;
}

function retrieveCampaignCategories()
{
    $campToCats = [];
    foreach (fetch_query('SELECT category, campaign FROM categories;') as $k => $tab) {
        $campToCats[$tab['campaign']] = $tab['category'];
    };
    return $campToCats;
}

function getUseUserSql($user, $target, $toUsersTable)
{

    $useUserSql = false;

    if ($toUsersTable) {
        $useUserSql = $toUsersTable;
    } else {
        $user_t = str_replace("User:", "", $user);
        $user_t = str_replace("user:", "", $user_t);
        // if target contains user
        if (strpos($target, $user_t) !== false) {
            $useUserSql = true;
        }
    }

    return $useUserSql;
}

// ---------
// Main API
// ---------

function add_to_db(
    string $target,
    string $lang,
    string $user,
    bool $toUsersTable,
    string $campaign,
    string $sourcetitle,
    string $mdwikiRevid,
    string $words,
    string $trType,
): array
{

    $sourcetitle = str_replace("_", " ", $sourcetitle);
    $target = str_replace("_", " ", $target);
    $user   = str_replace("_", " ", $user);

    if (empty($user) || empty($sourcetitle) || empty($lang)) {
        return [
            'use_user_sql' => false,
            'to_users_table' => $toUsersTable,
            'one_empty' => ['title' => $sourcetitle, 'lang' => $lang, 'user' => $user],
        ];
    }

    $campToCat = retrieveCampaignCategories();
    $cat = $campToCat[$campaign] ?? '';

    $useUserSql = getUseUserSql($user, $target, $toUsersTable);
    $tableName = ($useUserSql) ? 'pages_users' : 'pages';

    $exists = find_exists_or_update($sourcetitle, $lang, $user, $target, $tableName);

    if ($exists) {
        return [
            'use_user_sql' => $useUserSql,
            'to_users_table' => $toUsersTable,
            'exists' => "already_in",
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
        'use_user_sql' => $useUserSql,
        'to_users_table' => $toUsersTable,
        'execute_query' => true,
    ];
}

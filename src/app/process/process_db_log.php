<?php

namespace Publish\EditProcess;
/*
Usage:
use function Publish\EditProcess\add_to_db;
*/

use function Publish\AddToDb\InsertPageTarget;
use function Publish\Sql\retrieveCampaignCategories;
use function Publish\Sql\find_exists_or_update;

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

function add_to_db($target, $lang, $user, $toUsersTable, $campaign, $sourcetitle, $mdwikiRevid, $words, $trType)
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

    InsertPageTarget($sourcetitle, $trType, $cat, $lang, $user, $target, $tableName, $mdwikiRevid, $words);

    return [
        'use_user_sql' => $useUserSql,
        'to_users_table' => $toUsersTable,
        'execute_query' => true,
    ];
}

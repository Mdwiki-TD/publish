<?php

namespace Publish\EditProcess;
/*
Usage:
use function Publish\EditProcess\add_to_db;
*/

use function Publish\AddToDb\InsertPageTarget;
use function Publish\Sql\retrieveCampaignCategories;
use function Publish\Sql\find_exists_or_update;

function getUseUserSql($user, $target, $to_users_table)
{

    $use_user_sql = false;

    if ($to_users_table) {
        $use_user_sql = $to_users_table;
    } else {
        $user_t = str_replace("User:", "", $user);
        $user_t = str_replace("user:", "", $user_t);
        // if target contains user
        if (strpos($target, $user_t) !== false) {
            $use_user_sql = true;
        }
    }

    return $use_user_sql;
}

function add_to_db($target, $lang, $user, $to_users_table, $campaign, $sourcetitle, $mdwiki_revid, $words, $tr_type)
{

    $sourcetitle = str_replace("_", " ", $sourcetitle);
    $target = str_replace("_", " ", $target);
    $user   = str_replace("_", " ", $user);

    if (empty($user) || empty($sourcetitle) || empty($lang)) {
        return [
            'use_user_sql' => false,
            'to_users_table' => $to_users_table,
            'one_empty' => ['title' => $sourcetitle, 'lang' => $lang, 'user' => $user],
        ];
    }

    $camp_to_cat = retrieveCampaignCategories();
    $cat = $camp_to_cat[$campaign] ?? '';

    $use_user_sql = getUseUserSql($user, $target, $to_users_table);
    $table_name = ($use_user_sql) ? 'pages_users' : 'pages';

    $sql_result = [
        'use_user_sql' => $use_user_sql,
        'to_users_table' => $to_users_table,
    ];

    $exists = find_exists_or_update($sourcetitle, $lang, $user, $target, $table_name);

    if ($exists) {
        $sql_result['exists'] = "already_in";
        return $sql_result;
    }

    InsertPageTarget($sourcetitle, $tr_type, $cat, $lang, $user, $target, $table_name, $mdwiki_revid, $words);

    $sql_result['execute_query'] = true;

    return $sql_result;
}

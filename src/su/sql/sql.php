<?php

namespace Publish\Sql;

/*
use function Publish\Sql\GetQidForMdtitle;
use function Publish\Sql\retrieveCampaignCategories;
use function Publish\Sql\find_exists_or_update;
*/

use function Publish\MdwikiSql\fetch_query;
use function Publish\MdwikiSql\execute_query;


function GetQidForMdtitle($title)
{
    return fetch_query("SELECT qid FROM qids WHERE title = ?", [$title]);
}

function retrieveCampaignCategories()
{
    $camp_to_cats = [];
    foreach (fetch_query('SELECT category, campaign FROM categories;') as $k => $tab) {
        $camp_to_cats[$tab['campaign']] = $tab['category'];
    };
    return $camp_to_cats;
}

function find_exists_or_update($title, $lang, $user, $target, $table_name)
{
    $allowed_tables = ['pages', 'pages_users']; // Add all valid table names
    if (!in_array($table_name, $allowed_tables, true)) {
        error_log("find_exists_or_update: Invalid table name: $table_name");
        return 0;
    }

    $query = <<<SQL
        SELECT * FROM $table_name WHERE title = ? AND lang = ? AND user = ?
    SQL;

    $result = fetch_query($query, [$title, $lang, $user]);

    if (count($result) > 0) {
        $query = <<<SQL
            UPDATE $table_name SET target = ?, pupdate = DATE(NOW())
            WHERE title = ? AND lang = ? AND user = ? AND (target = "" OR target IS NULL)
        SQL;
        $params = [$target, $title, $lang, $user];
        execute_query($query, $params, $table_name);
    }
    return count($result) > 0;
}

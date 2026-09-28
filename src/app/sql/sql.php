<?php

namespace Publish\Sql;

use function Publish\MdwikiSql\fetch_query;
use function Publish\MdwikiSql\execute_query;


function GetQidForMdtitle($title)
{
    return fetch_query("SELECT qid FROM qids WHERE title = ?", [$title]);
}

function retrieveCampaignCategories()
{
    $campToCats = [];
    foreach (fetch_query('SELECT category, campaign FROM categories;') as $k => $tab) {
        $campToCats[$tab['campaign']] = $tab['category'];
    };
    return $campToCats;
}

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

<?php

namespace Publish\AddToDb;
/*

use function Publish\AddToDb\InsertPublishReports;
use function Publish\AddToDb\InsertPageTarget;
*/

use function Publish\MdwikiSql\execute_query;

function InsertPublishReports($title, $user, $lang, $sourcetitle, $result, $data)
{
    // Validate required parameters
    /*
    if (empty($title) || empty($user) || empty($lang)) {
        error_log("InsertPublishReports: Missing required parameters");
        return false;
    }
    */
    $query = "INSERT INTO publish_reports (`date`, `title`, `user`, `lang`, `sourcetitle`, `result`, `data`) VALUES (NOW(), ?, ?, ?, ?, ?, ?)";
    $reportData = json_encode($data);
    // remove .json from $result
    $result = str_replace(".json", "", $result);
    $params = [$title, $user, $lang, $sourcetitle, $result, $reportData];
    execute_query($query, $params, "publish_reports");
}

function InsertPageTarget($sourcetitle, $trType, $cat, $lang, $user, $target, $tableName, $mdwikiRevid, $words)
{
    $allowedTables = ['pages', 'pages_users']; // Add all valid table names
    if (!in_array($tableName, $allowedTables, true)) {
        error_log("find_exists_or_update: Invalid table name: $tableName");
        return false;
    }
    $query = <<<SQL
        INSERT INTO $tableName (title, word, translate_type, cat, lang, user, pupdate, target, mdwiki_revid)
        SELECT ?, ?, ?, ?, ?, ?, DATE(NOW()), ?, ?
    SQL;

    $params = [
        $sourcetitle,
        $words,
        $trType,
        $cat,
        $lang,
        $user,
        $target,
        $mdwikiRevid
    ];
    execute_query($query, $params, $tableName);
}

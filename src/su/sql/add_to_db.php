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
    $report_data = json_encode($data);
    // remove .json from $result
    $result = str_replace(".json", "", $result);
    $params = [$title, $user, $lang, $sourcetitle, $result, $report_data];
    execute_query($query, $params, "publish_reports");
}

function InsertPageTarget($sourcetitle, $tr_type, $cat, $lang, $user, $target, $table_name, $mdwiki_revid, $words)
{

    $query = <<<SQL
        INSERT INTO $table_name (title, word, translate_type, cat, lang, user, pupdate, target, mdwiki_revid)
        SELECT ?, ?, ?, ?, ?, ?, DATE(NOW()), ?, ?
    SQL;

    $params = [
        $sourcetitle,
        $words,
        $tr_type,
        $cat,
        $lang,
        $user,
        $target,
        $mdwiki_revid
    ];
    execute_query($query, $params, $table_name);
}

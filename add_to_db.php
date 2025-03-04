<?php

namespace Publish\AddToDb;
/*

use function Publish\AddToDb\InsertPageTarget;
use function Publish\AddToDb\retrieveCampaignCategories;
*/

include_once __DIR__ . '/include.php';

use function Publish\MdwikiSql\fetch_query;
use function Publish\MdwikiSql\execute_query;

try {
    $file = file_get_contents(__DIR__ . "/../td/Tables/jsons/words.json");
    // $file = file_get_contents("https://mdwiki.toolforge.org/td/Tables/jsons/words.json");
    $Words_table = json_decode($file, true);
} catch (\Exception $e) {
    $Words_table = [];
}

function retrieveCampaignCategories()
{
    $camp_to_cats = [];
    foreach (fetch_query('select id, category, category2, campaign, depth, def from categories;') as $k => $tab) {
        $camp_to_cats[$tab['campaign']] = $tab['category'];
    };
    return $camp_to_cats;
}

function find_exists($title, $lang, $user, $target, $use_user_sql)
{
    $query = <<<SQL
        SELECT 1 FROM (
            SELECT 1 FROM pages WHERE title = ? AND lang = ? AND user = ? AND target != ""
            UNION
            SELECT 1 FROM pages_users WHERE title = ? AND lang = ? AND user = ? AND target != ""
        ) AS combined
    SQL;
    // ---
    $params = [$title, $lang, $user, $title, $lang, $user];
    // ---
    $result = fetch_query($query, $params);
    // ---
    return count($result) > 0;
}

function find_exists_or_update($title, $lang, $user, $target, $use_user_sql)
{
    // ---
    $table_name = $use_user_sql ? 'pages_users' : 'pages';
    // ---
    $query = <<<SQL
        SELECT * FROM $table_name WHERE title = ? AND lang = ? AND user = ?
    SQL;
    // ---
    $result = fetch_query($query, [$title, $lang, $user]);
    // ---
    // today date like: 2024-08-21
    $today = date("Y-m-d");
    // ---
    if (count($result) > 0) {
        $query = <<<SQL
            UPDATE $table_name SET target = ?, pupdate = ?
            WHERE title = ? AND lang = ? AND user = ? AND target = ""
        SQL;
        // ---
        $params = [$target, $today, $title, $lang, $user];
        // ---
        execute_query($query, $params);
    }
    // ---
    return count($result) > 0;
}

function InsertPageTarget($title, $tr_type, $cat, $lang, $user, $test, $target)
{
    global $Words_table;
    // ---
    $use_user_sql = false;
    // ---
    if (empty($user) || empty($title) || empty($lang)) {
        return $use_user_sql;
    }
    // ---
    // today date like: 2024-08-21
    $today = date("Y-m-d");
    // ---
    $word = $Words_table[$title] ?? 0;
    // ---
    $target = str_replace("_", " ", $target);
    $user   = str_replace("_", " ", $user);
    // ---
    $user_t = str_replace("User:", "", $user);
    $user_t = str_replace("user:", "", $user_t);
    // ---
    // if target contains user
    if (strpos($target, $user_t) !== false) {
        $use_user_sql = true;
        if ($user == "Mr. Ibrahem") {
            return $use_user_sql;
        }
    }
    // ---
    $exists = find_exists_or_update($title, $lang, $user, $target, $use_user_sql);
    // ---
    if ($exists) {
        return $use_user_sql;
    }
    // ---
    $query_user = <<<SQL
        INSERT INTO pages_users (title, lang, user, pupdate, target, add_date)
        SELECT ?, ?, ?, ?, ?, now()
    SQL;
    // ---
    $query_user_params = [$title, $lang, $user, $today, $target];
    // ---
    $query = <<<SQL
        INSERT INTO pages (title, word, translate_type, cat, lang, date, user, pupdate, target, add_date)
        SELECT ?, ?, ?, ?, ?, now(), ?, ?, ?, now()
    SQL;
    // ---
    $params = [
        $title, $word, $tr_type, $cat, $lang,
        $user,
        $today,
        $target
    ];
    // ---
    // if $title has $user in it then use $query_user else use $query
    if ($use_user_sql) {
        $query = $query_user;
        $params = $query_user_params;
    }
    // ---
    if (!empty($test)) {
        echo "<br>$query<br>";
    }
    execute_query($query, $params = $params);
    // ---
    return $use_user_sql;
}

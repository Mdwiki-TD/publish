<?php

namespace Publish\Revids;
/*
Usage:
use function Publish\Revids\get_revid_db;
use function Publish\Revids\get_revid;
*/

use function Publish\Helps\pub_test_print;
use function Publish\Helps\get_url_curl;

function get_revid_db($sourcetitle)
{
    // ---
    $url = "https://mdwiki.toolforge.org/api.php?get=revids&title=$sourcetitle";
    // ---
    // $json = file_get_contents($url);
    $json = get_url_curl($url);
    // ---
    print_r($json);
    // ---
    $json = json_decode($json, true);
    // ---
    $revid = $json[$sourcetitle] ?? "";
    // ---
    return $revid;
}

function get_revid($sourcetitle)
{
    // read all_pages_revids.json file
    $revids_file = __DIR__ . '/all_pages_revids.json';
    // ---
    if (!file_exists($revids_file)) $revids_file = __DIR__ . '/../all_pages_revids.json';
    // ---
    try {
        $json = json_decode(file_get_contents($revids_file), true);
        $revid = $json[$sourcetitle] ?? "";
        return $revid;
    } catch (\Exception $e) {
        pub_test_print($e->getMessage());
    }
    return "";
}

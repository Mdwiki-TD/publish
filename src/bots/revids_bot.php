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
    $params = [
        "get" => "revids",
        "title" => $sourcetitle
    ];
    // ---
    if (($_SERVER['SERVER_NAME'] ?? "localhost") == "localhost") {
        $url = "http://localhost:9001/api?" . http_build_query($params, '', '&', PHP_QUERY_RFC3986);
        $json = file_get_contents($url);
    } else {
        $url = "https://mdwiki.toolforge.org/api.php?" . http_build_query($params, '', '&', PHP_QUERY_RFC3986);
        $json = get_url_curl($url);
    }
    // ---
    $json = json_decode($json, true);
    // ---
    $results = array_column($json["results"] ?? [], "revid", "title");
    // ---
    $revid = $results[$sourcetitle] ?? "";
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

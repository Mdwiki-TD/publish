<?php

namespace Publish\Revids;

use Publish\CurlRequests\CurlHttpClient;

use function Publish\Helps\pub_test_print;

function get_revid_db($sourcetitle)
{
    $params = [
        "get" => "revids",
        "title" => $sourcetitle
    ];
    if (($_SERVER['SERVER_NAME'] ?? "localhost") == "localhost") {
        $url = "http://localhost:9001/api?" . http_build_query($params, '', '&', PHP_QUERY_RFC3986);
        $json = file_get_contents($url);
    } else {
        $url = "https://mdwiki.toolforge.org/api.php?" . http_build_query($params, '', '&', PHP_QUERY_RFC3986);

        $client = new CurlHttpClient();
        $json = $client->get($url) ?? '';
    }
    $json = json_decode($json, true);
    $results = array_column($json["results"] ?? [], "revid", "title");
    $revid = $results[$sourcetitle] ?? "";
    return $revid;
}

function get_revid($sourcetitle)
{
    // read all_pages_revids.json file
    $revidsFilePath = getenv("ALL_PAGES_REVIDS_PATH") ?: ($_ENV['ALL_PAGES_REVIDS_PATH'] ?? "");
    if (empty($revidsFilePath)) {
        error_log("ALL_PAGES_REVIDS_PATH is not set");
        $revidsFilePath = __DIR__ . '/all_pages_revids.json';
    };
    if (!file_exists($revidsFilePath)) {
        error_log("all_pages_revids.json file not found");
        return "";
    }
    try {
        $json = json_decode(file_get_contents($revidsFilePath), true);
        $revid = $json[$sourcetitle] ?? "";
        return $revid;
    } catch (\Exception $e) {
        pub_test_print($e->getMessage());
    }
    return "";
}

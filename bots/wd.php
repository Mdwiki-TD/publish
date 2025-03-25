<?php

namespace Publish\WD;
/*
use function Publish\WD\LinkToWikidata;
use function Publish\WD\GetQidForMdtitle;
*/

include_once __DIR__ . '/../include.php';

use function Publish\GetToken\post_params;
use function Publish\MdwikiSql\fetch_query;
use function Publish\Helps\get_access_from_db;
use function Publish\Helps\pub_test_print;
use function Publish\Helps\get_url_curl;


function GetQidForMdtitle($title)
{
    $query = <<<SQL
        SELECT qid FROM qids WHERE title = ?
    SQL;
    // ---
    $params = [$title];
    // ---
    $result = fetch_query($query, $params);
    // ---
    return $result;
}

function GetTitleInfoOld($targettitle, $lang)
{
    // replace '/' with '%2F'
    $targettitle = urlencode($targettitle);
    // $targettitle = str_replace('/', '%2F', $targettitle);
    // $targettitle = str_replace(' ', '_', $targettitle);
    // ---
    $url = "https://$lang.wikipedia.org/api/rest_v1/page/summary/$targettitle";
    // ---
    pub_test_print("GetTitleInfo url: $url");
    // ---
    try {
        $result = get_url_curl($url);
        pub_test_print("GetTitleInfo result: $result");
        $result = json_decode($result, true);
    } catch (\Exception $e) {
        pub_test_print("GetTitleInfo: $e");
        $result = null;
    }
    // ---
    return $result;
}

function GetTitleInfo($targettitle, $lang)
{
    // ---
    $params = [
        "action" => "query",
        "format" => "json",
        "titles" => $targettitle,
        "utf8" => 1,
        "formatversion" => "2"
    ];
    // ---
    $url = "https://$lang.wikipedia.org/w/api.php" . "?" . http_build_query($params);
    // ---
    pub_test_print("GetTitleInfo url: $url");
    // ---
    try {
        $result = get_url_curl($url);
        pub_test_print("GetTitleInfo result: $result");
        $result = json_decode($result, true);
        // { "query": { "pages": [ { "pageid": 5049507, "ns": 2, "title": "利用者:Mr. Ibrahem/オランザピン/サミドルファン" } ] } }
        $result = $result['query']['pages'][0];
    } catch (\Exception $e) {
        pub_test_print("GetTitleInfo: $e");
        $result = null;
    }
    // ---
    return $result;
}

function LinkIt($qid, $lang, $sourcetitle, $targettitle, $access_key, $access_secret)
{
    $https_domain = "https://www.wikidata.org";
    // ---
    $apiParams = [
        "action" => "wbsetsitelink",
        "linktitle" => $targettitle,
        "linksite" => "{$lang}wiki",
    ];
    if (!empty($qid)) {
        $apiParams["id"] = $qid;
    } else {
        $apiParams["title"] = $sourcetitle;
        $apiParams["site"] = "enwiki";
    }
    // ---
    $response = post_params($apiParams, $https_domain, $access_key, $access_secret);
    // ---
    $Result = json_decode($response, true) ?? [];
    // ---
    // if (isset($Result->error)) {
    if (isset($Result['error'])) {
        pub_test_print("post_params: Result->error: " . json_encode($Result['error']));
    }
    // ---
    if ($Result == null) {
        pub_test_print("post_params: Error: " . json_last_error() . " " . json_last_error_msg());
        pub_test_print("response:");
        pub_test_print($response);
    }
    // ---
    return $Result;
}
function getAccessCredentials($user, $access_key, $access_secret)
{
    if (!$access_key || !$access_secret) {
        $access = get_access_from_db($user);
        if ($access === null) {
            pub_test_print("user = $user");
            pub_test_print("access == null");
            return null;
        }
        $access_key = $access['access_key'];
        $access_secret = $access['access_secret'];
    }
    // ---
    return [$access_key, $access_secret];
}

function LinkToWikidata($sourcetitle, $lang, $user, $targettitle, $access_key, $access_secret)
{
    $qids = GetQidForMdtitle($sourcetitle);
    $qid = $qids[0]['qid'] ?? '';

    $credentials = getAccessCredentials($user, $access_key, $access_secret);
    if ($credentials === null) {
        return ['error' => 'Access credentials not found for user: ' . $user, 'qid' => $qid];
    }
    list($access_key, $access_secret) = $credentials;

    $link_result = LinkIt($qid, $lang, $sourcetitle, $targettitle, $access_key, $access_secret) ?? [];

    $link_result["qid"] = $qid;

    if (isset($link_result['success']) && $link_result['success']) {
        pub_test_print("success: true");
        return ['result' => "success", 'qid' => $qid];
    }

    return $link_result;
}

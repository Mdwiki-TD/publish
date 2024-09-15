<?php

namespace Publish\WD;
/*
use function Publish\WD\LinkToWikidata;
use function Publish\WD\GetQidForMdtitle;
*/

include_once __DIR__ . '/include.php';

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

function GetTitleInfo($targettitle, $lang)
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
    $Result = json_decode($response, true);
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
    $credentials = getAccessCredentials($user, $access_key, $access_secret);
    if ($credentials === null) {
        return ['error' => 'Access credentials not found for user: ' . $user];
    }
    list($access_key, $access_secret) = $credentials;

    $qids = GetQidForMdtitle($sourcetitle);
    $qid = $qids[0]['qid'] ?? '';

    $title_info = GetTitleInfo($targettitle, $lang);

    $ns = $title_info['namespace']['id'] ?? '';
    if ($ns !== 0 && $ns !== "0") {
        return ['error' => 'Cannot create link for namespace: ' . $ns];
    }

    // "title":"Not found."
    $not_found = $title_info['title'] ?? "";
    pub_test_print("ns: ($ns), not_found: ($not_found)");
    pub_test_print(json_encode($title_info));

    $not_found = $title_info['title'] ?? '';
    if ($not_found === 'Not found.') {
        return ['error' => 'Target page not found: ' . $targettitle];
    }

    $link_result = LinkIt($qid, $lang, $sourcetitle, $targettitle, $access_key, $access_secret);

    if (isset($link_result['success']) && $link_result['success']) {
        pub_test_print("success: true");
        return ['result' => "success"];
    }

    return $link_result;
}

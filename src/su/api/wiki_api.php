<?php

namespace Publish\WikiApi;
/*
use function Publish\WikiApi\GetTitleInfo;
*/

use function Publish\Helps\pub_test_print;
use function Publish\CurlRequests\get_url_curl;


function GetTitleInfo($targettitle, $lang)
{
    $params = [
        "action" => "query",
        "format" => "json",
        "titles" => $targettitle,
        "utf8" => 1,
        "formatversion" => "2"
    ];
    $url = "https://$lang.wikipedia.org/w/api.php" . "?" . http_build_query($params, '', '&', PHP_QUERY_RFC3986);
    pub_test_print("GetTitleInfo url: $url");
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
    return $result;
}

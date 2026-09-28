<?php

namespace Publish\WikiApi;

use Publish\CurlRequests\CurlHttpClient;


function debugEcho(string $s): void
{
    if (!isset($_REQUEST['test'])) return;
    echo "\n<br>\n$s";
}

function GetTitleInfo(string $targettitle, string $lang)
{
    $params = [
        "action" => "query",
        "format" => "json",
        "titles" => $targettitle,
        "utf8" => 1,
        "formatversion" => "2"
    ];
    $url = "https://$lang.wikipedia.org/w/api.php" . "?" . http_build_query($params, '', '&', PHP_QUERY_RFC3986);

    debugEcho("GetTitleInfo url: $url");

    try {
        $client = new CurlHttpClient();
        $output = $client->get($url) ?? '';
        debugEcho("GetTitleInfo result: $output");
        $result = json_decode($output, true);

        if (!is_array($result)) return null;

        // { "query": { "pages": [ { "pageid": 5049507, "ns": 2, "title": "利用者:Mr. Ibrahem/オランザピン/サミドルファン" } ] } }
        $result = $result['query']['pages'][0] ?: null;
    } catch (\Exception $e) {
        debugEcho("GetTitleInfo: $e");
        $result = null;
    }
    return $result;
}

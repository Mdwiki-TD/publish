<?php

namespace Publish\GetToken;

use function Publish\Helps\pub_test_print;
use function Publish\MediaWikiClient\postParams;

function get_cxtoken($wiki, $accessKey, $accessSecret)
{
    $httpsDomain = "https://$wiki.wikipedia.org";
    $apiParams = [
        'action' => 'cxtoken',
        'format' => 'json',
    ];
    $response = postParams($apiParams, $httpsDomain, $accessKey, $accessSecret);

    $apiResult = json_decode($response, true);

    if ($apiResult == null || isset($apiResult['error'])) {
        pub_test_print("<br>get_cxtoken: Error: " . json_last_error() . " " . json_last_error_msg());
    }

    return $apiResult;
}

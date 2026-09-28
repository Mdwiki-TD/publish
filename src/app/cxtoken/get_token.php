<?php

namespace Publish\GetToken;

use Publish\MediaWikiClient\MediaWikiEditClient;

use function Publish\Helps\pub_test_print;

function get_cxtoken($wiki, $accessKey, $accessSecret)
{
    $httpsDomain = "https://$wiki.wikipedia.org";
    $apiParams = [
        'action' => 'cxtoken',
        'format' => 'json',
    ];

    $editClient = new MediaWikiEditClient();
    $response = $editClient->postParams(
        (array) $apiParams,
        (string) $httpsDomain,
        (string) $accessKey,
        (string) $accessSecret,
    );

    $apiResult = json_decode($response, true);

    if ($apiResult == null || isset($apiResult['error'])) {
        pub_test_print("<br>get_cxtoken: Error: " . json_last_error() . " " . json_last_error_msg());
    }

    return $apiResult;
}

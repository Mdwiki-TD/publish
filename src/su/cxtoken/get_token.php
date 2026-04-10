<?php

namespace Publish\GetToken;
/*

use function Publish\GetToken\get_cxtoken;

*/

use function Publish\Helps\pub_test_print;
use function Publish\MediaWikiClient\post_params;

function get_cxtoken($wiki, $access_key, $access_secret)
{
    $https_domain = "https://$wiki.wikipedia.org";
    $apiParams = [
        'action' => 'cxtoken',
        'format' => 'json',
    ];
    $response = post_params($apiParams, $https_domain, $access_key, $access_secret);

    $apiResult = json_decode($response, true);

    if ($apiResult == null || isset($apiResult['error'])) {
        pub_test_print("<br>get_cxtoken: Error: " . json_last_error() . " " . json_last_error_msg());
    }

    return $apiResult;
}

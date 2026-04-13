<?php

namespace Publish\CurlRequests;
/*
Usage:
use function Publish\CurlRequests\get_url_curl;
*/

use function Publish\Helps\pub_test_print;

function get_url_curl(string $url): string
{
    $usr_agent = 'WikiProjectMed Translation Dashboard/1.0 (https://mdwiki.toolforge.org/; tools.mdwiki@toolforge.org)';

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    // curl_setopt($ch, CURLOPT_COOKIEJAR, "cookie.txt");
    // curl_setopt($ch, CURLOPT_COOKIEFILE, "cookie.txt");

    curl_setopt($ch, CURLOPT_USERAGENT, $usr_agent);

    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);

    $output = curl_exec($ch);
    if ($output === FALSE) {
        pub_test_print("<br>cURL Error: " . curl_error($ch) . "<br>$url");
    }

    curl_close($ch);

    return $output;
}

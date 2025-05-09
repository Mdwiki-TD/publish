<?php

namespace Publish\Helps;
/*
Usage:
include_once __DIR__ . '/../publish/helps.php';
use function Publish\Helps\pub_test_print;
use function Publish\Helps\encode_value;
use function Publish\Helps\decode_value;
*/

include_once __DIR__ . '/include.php';

use Defuse\Crypto\Crypto;

$usr_agent = 'WikiProjectMed Translation Dashboard/1.0 (https://mdwiki.toolforge.org/; tools.mdwiki@toolforge.org)';

function pub_test_print($s)
{
    //---
    if (!isset($_REQUEST['test'])) return;
    //---
    if (gettype($s) == 'string') {
        echo "\n<br>\n$s";
    } else {
        echo "\n<br>\n";
        print_r($s);
    }
}

function decode_value($value, $key_type = "cookie")
{
    global $cookie_key, $decrypt_key;
    // ---
    if (empty(trim($value))) {
        return "";
    }
    // ---
    $use_key = ($key_type == "decrypt") ? $decrypt_key : $cookie_key;
    // ---
    try {
        $value = Crypto::decrypt($value, $use_key);
    } catch (\Exception $e) {
        $value = "";
    }
    return $value;
}

function encode_value($value, $key_type = "cookie")
{
    global $cookie_key, $decrypt_key;
    // ---
    $use_key = ($key_type == "decrypt") ? $decrypt_key : $cookie_key;
    // ---
    if (empty(trim($value))) {
        return "";
    }
    // ---
    try {
        $value = Crypto::encrypt($value, $use_key);
    } catch (\Exception $e) {
        $value = "";
    };
    return $value;
}

function get_url_curl(string $url): string
{
    global $usr_agent;

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

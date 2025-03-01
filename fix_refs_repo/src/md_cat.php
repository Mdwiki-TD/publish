<?php

namespace WpRefs\MdCat;
/*

use function Publish\MdCat\Add_MdWiki_Category;

*/

use function WpRefs\TestBot\echo_test;

$usr_agent = 'WikiProjectMed Translation Dashboard/1.0 (https://mdwiki.toolforge.org/; tools.mdwiki@toolforge.org)';

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
        echo_test("<br>cURL Error: " . curl_error($ch) . "<br>$url");
    }

    curl_close($ch);

    return $output;
}
function get_cats()
{
    $url = "https://www.wikidata.org/w/rest.php/wikibase/v1/entities/items/Q107014860/sitelinks";
    // ---
    // $json = file_get_contents($url);
    $json = get_url_curl($url);
    // ---
    // echo $json;
    // ---
    $json = json_decode($json, true);
    // ---
    return $json;
}

function Add_MdWiki_Category($lang)
{
    // ---
    $cats = get_cats();
    // ---
    $cat = $cats[$lang . "wiki"]["title"] ?? "Category:Translated from MDWiki";
    // ---
    return $cat;
}

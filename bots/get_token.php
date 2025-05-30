<?php

namespace Publish\GetToken;
/*
use function Publish\GetToken\get_client;
use function Publish\GetToken\get_csrftoken;
use function Publish\GetToken\get_cxtoken;
use function Publish\GetToken\post_params;
*/

include_once __DIR__ . '/../include.php';

use MediaWiki\OAuthClient\Client;
use MediaWiki\OAuthClient\ClientConfig;
use MediaWiki\OAuthClient\Consumer;
use MediaWiki\OAuthClient\Token;
use function Publish\Helps\pub_test_print;

function get_client($wiki, $oauthUrl = "")
{
    global $gUserAgent, $consumerKey, $consumerSecret;
    // ---
    if (!empty($wiki)) {
        $oauthUrl = "https://$wiki.wikipedia.org/w/index.php?title=Special:OAuth";
    }
    // ---
    // Configure the OAuth client with the URL and consumer details.
    $conf = new ClientConfig($oauthUrl);
    $conf->setConsumer(new Consumer($consumerKey, $consumerSecret));
    $conf->setUserAgent($gUserAgent);
    $client = new Client($conf);
    // ---
    return $client;
}

function get_csrftoken($client, $access_key, $access_secret, $apiUrl)
{
    $accessToken = new Token($access_key, $access_secret);
    // ---
    $response = $client->makeOAuthCall($accessToken, "$apiUrl?action=query&meta=tokens&format=json");
    // ---
    $data = json_decode($response, true);
    // ---
    if ($data == null || !isset($data['query']['tokens']['csrftoken'])) {
        // Handle error
        pub_test_print("<br>get_csrftoken Error: " . json_last_error() . " " . json_last_error_msg());
        pub_test_print($data);
    }
    // ---
    return $data;
}

function post_params($apiParams, $https_domain, $access_key, $access_secret)
{
    // ---
    $apiUrl = "$https_domain/w/api.php";
    // ---
    $client = get_client("", $oauthUrl = "$https_domain/w/index.php?title=Special:OAuth");
    // ---
    $accessToken = new Token($access_key, $access_secret);
    // ---
    $csrftoken_data = get_csrftoken($client, $access_key, $access_secret, $apiUrl);
    $csrftoken = $csrftoken_data['query']['tokens']['csrftoken'];
    // ---
    if ($csrftoken == null) {
        return json_encode(['error' => 'get_csrftoken failed', "rand" => rand(), "csrftoken_data" => $csrftoken_data], JSON_PRETTY_PRINT);
    }
    // ---
    $apiParams["format"] = "json";
    $apiParams["token"] = $csrftoken;
    // ---
    pub_test_print("post_params: apiParams:" . json_encode($apiParams, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    // ---
    $response = $client->makeOAuthCall($accessToken, $apiUrl, true, $apiParams);
    // ---
    return $response;
}
function get_cxtoken($wiki, $access_key, $access_secret)
{
    $https_domain = "https://$wiki.wikipedia.org";
    // ---
    $apiParams = [
        'action' => 'cxtoken',
        'format' => 'json',
    ];
    // ---
    $response = post_params($apiParams, $https_domain, $access_key, $access_secret);
    // ---
    $apiResult = json_decode($response, true);
    // ---
    if ($apiResult == null || isset($apiResult['error'])) {
        pub_test_print("<br>get_cxtoken: Error: " . json_last_error() . " " . json_last_error_msg());
    }
    // ---
    return $apiResult;
}

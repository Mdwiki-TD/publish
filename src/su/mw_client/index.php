<?php

namespace Publish\MediaWikiClient;
/*
use function Publish\MediaWikiClient\get_client;
use function Publish\MediaWikiClient\getAccessToken;
use function Publish\MediaWikiClient\get_csrftoken;
use function Publish\MediaWikiClient\post_params;
use function Publish\MediaWikiClient\get_edits_token;
*/

use MediaWiki\OAuthClient\Client;
use MediaWiki\OAuthClient\ClientConfig;
use MediaWiki\OAuthClient\Consumer;
use MediaWiki\OAuthClient\Token;
use function Publish\Helps\pub_test_print;

function get_client($domain)
{
    $CONSUMER_KEY        = getenv("CONSUMER_KEY") ?: '';
    $CONSUMER_SECRET     = getenv("CONSUMER_SECRET") ?: '';
    $oauthUrl = "https://$domain/w/index.php?title=Special:OAuth";

    // Configure the OAuth client with the URL and consumer details.
    $conf = new ClientConfig($oauthUrl);

    $conf->setConsumer(new Consumer($CONSUMER_KEY, $CONSUMER_SECRET));

    $conf->setUserAgent('mdwiki MediaWiki OAuth Client/1.0');

    $client = new Client($conf);

    return $client;
}

function getAccessToken($access_key, $access_secret)
{

    $accessToken = new Token($access_key, $access_secret);
    return $accessToken;
}

function get_edits_token($client, $accessToken, $apiUrl)
{
    $response = $client->makeOAuthCall($accessToken, "$apiUrl?action=query&meta=tokens&format=json");
    $data = json_decode($response);
    if ($data == null || !isset($data->query->tokens->csrftoken)) {
        // Handle error
        pub_test_print("<br>get_edits_token Error: " . json_last_error() . " " . json_last_error_msg());
        return null;
    }
    return $data->query->tokens->csrftoken;
}

function get_csrftoken($client, $access_key, $access_secret, $apiUrl)
{
    $accessToken = getAccessToken($access_key, $access_secret);
    $response = $client->makeOAuthCall($accessToken, "$apiUrl?action=query&meta=tokens&format=json");
    $data = json_decode($response, true);
    if ($data == null || !isset($data['query']['tokens']['csrftoken'])) {
        // Handle error
        pub_test_print("<br>get_csrftoken Error: " . json_last_error() . " " . json_last_error_msg());
        pub_test_print($data);
    }
    return $data;
}

function post_params($apiParams, $https_domain, $access_key, $access_secret)
{
    $apiUrl = "$https_domain/w/api.php";
    $client = get_client($https_domain);

    $accessToken = new Token($access_key, $access_secret);

    $csrftoken_data = get_csrftoken($client, $access_key, $access_secret, $apiUrl);

    $csrftoken = $csrftoken_data['query']['tokens']['csrftoken'] ?? null;

    if ($csrftoken == null) {
        $data = [
            'error' => 'get_csrftoken failed',
            "rand" => rand(),
            "csrftoken_data" => $csrftoken_data
        ];
        return json_encode($data, JSON_PRETTY_PRINT);
    }

    $apiParams["format"] = "json";
    $apiParams["token"] = $csrftoken;

    pub_test_print("post_params: apiParams:" . json_encode($apiParams, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

    $response = $client->makeOAuthCall($accessToken, $apiUrl, true, $apiParams);

    return $response;
}

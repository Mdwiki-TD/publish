<?php

namespace Publish\DoEdit;
/*
Usage:
use function Publish\DoEdit\publish_do_edit;
use function Publish\DoEdit\get_edits_token;
*/

include_once __DIR__ . '/../include.php';

use MediaWiki\OAuthClient\Client;
use MediaWiki\OAuthClient\ClientConfig;
use MediaWiki\OAuthClient\Consumer;
use MediaWiki\OAuthClient\Token;
use function Publish\Helps\pub_test_print;

function get_edits_token($client, $accessToken, $apiUrl)
{
    $response = $client->makeOAuthCall($accessToken, "$apiUrl?action=query&meta=tokens&format=json");
    // ---
    $data = json_decode($response);
    // ---
    if ($data == null || !isset($data->query->tokens->csrftoken)) {
        // Handle error
        pub_test_print("<br>get_edits_token Error: " . json_last_error() . " " . json_last_error_msg());
        return null;
    }
    // ---
    return $data->query->tokens->csrftoken;
}

function publish_do_edit($apiParams, $wiki, $access_key, $access_secret)
{
    global $gUserAgent, $consumerKey, $consumerSecret;
    // ---
    $oauthUrl = "https://$wiki.wikipedia.org/w/index.php?title=Special:OAuth";
    $apiUrl = "https://$wiki.wikipedia.org/w/api.php";
    // ---
    // Configure the OAuth client with the URL and consumer details.
    $conf = new ClientConfig($oauthUrl);
    $conf->setConsumer(new Consumer($consumerKey, $consumerSecret));
    $conf->setUserAgent($gUserAgent);
    $client = new Client($conf);
    // ---
    $accessToken = new Token($access_key, $access_secret);
    // ---
    $editToken = get_edits_token($client, $accessToken, $apiUrl);
    // ---
    $apiParams['token'] = $editToken;
    // ---
    $req = $client->makeOAuthCall(
        $accessToken,
        $apiUrl,
        true,
        $apiParams
    );
    // ---
    $editResult = json_decode($req, true);
    // ---
    return $editResult;
}

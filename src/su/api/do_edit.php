<?php

namespace Publish\DoEdit;
/*
Usage:
use function Publish\DoEdit\publish_do_edit;
*/


use function Publish\MediaWikiClient\get_client;
use function Publish\MediaWikiClient\getAccessToken;
use function Publish\MediaWikiClient\get_edits_token;

function publish_do_edit($apiParams, $wiki, $access)
{
    $client = get_client("$wiki.wikipedia.org");

    $apiUrl = "https://$wiki.wikipedia.org/w/api.php";

    $accessToken = getAccessToken($access["access_key"], $access["access_secret"]);

    $editToken = get_edits_token($client, $accessToken, $apiUrl);

    $apiParams['token'] = $editToken;

    $req = $client->makeOAuthCall(
        $accessToken,
        $apiUrl,
        true,
        $apiParams
    );

    $editResult = json_decode($req, true);

    return $editResult;
}

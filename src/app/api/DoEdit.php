<?php

namespace Publish\DoEdit;

use function Publish\MediaWikiClient\get_client;
use function Publish\MediaWikiClient\getEditsToken;

use MediaWiki\OAuthClient\Token;

function publish_do_edit($apiParams, $wiki, Token $accessToken)
{
    $client = get_client("$wiki.wikipedia.org");

    $apiUrl = "https://$wiki.wikipedia.org/w/api.php";

    $editToken = getEditsToken($client, $accessToken, $apiUrl);

    $apiParams['token'] = $editToken;
    # Error details: The following tags are not allowed to be manually applied: contenttranslation and contenttranslation-v2
    # $apiParams['tags'] = 'contenttranslation|contenttranslation-v2';

    $req = $client->makeOAuthCall(
        $accessToken,
        $apiUrl,
        true,
        $apiParams
    );

    $editResult = json_decode($req, true);

    return $editResult;
}

<?php

namespace Publish\DoEdit;

use Publish\MediaWikiClient\MediaWikiOAuthClientFactory;
use Publish\MediaWikiClient\MediaWikiEditClient;

use MediaWiki\OAuthClient\Token;

function publish_do_edit(array $apiParams, string $wiki, Token $accessToken)
{
    $clientFactory = new MediaWikiOAuthClientFactory();
    $client = $clientFactory->createForDomain("$wiki.wikipedia.org");

    $apiUrl = "https://$wiki.wikipedia.org/w/api.php";

    // check if apiParams already has a token
    if (!isset($apiParams['token'])) {
        $editClient = new MediaWikiEditClient();
        $apiParams['token'] = $editClient->getEditsToken($client, $accessToken, $apiUrl);
    }

    # Error details: The following tags are not allowed to be manually applied: contenttranslation and contenttranslation-v2
    # $apiParams['tags'] = 'contenttranslation|contenttranslation-v2';

    $req = $client->makeOAuthCall(
        $accessToken,
        $apiUrl,
        true,
        $apiParams
    );

    return json_decode($req, true);
}

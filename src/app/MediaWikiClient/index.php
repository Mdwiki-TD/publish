<?php

namespace Publish\MediaWikiClient;

use MediaWiki\OAuthClient\Client;
use MediaWiki\OAuthClient\ClientConfig;
use MediaWiki\OAuthClient\Consumer;
use MediaWiki\OAuthClient\Token;
use function Publish\Helps\pub_test_print;

function get_client($domain)
{
        $domain = parse_url($domain, PHP_URL_HOST);
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

function getAccessToken($accessKey, $accessSecret)
{

    $accessToken = new Token($accessKey, $accessSecret);
    return $accessToken;
}

function getEditsToken($client, Token $accessToken, string $apiUrl): ?string
{
        $response = $client->makeOAuthCall($accessToken, "$apiUrl?action=query&meta=tokens&format=json");
        $data = json_decode($response);

        if ($data === null || !isset($data->query->tokens->csrftoken)) {
            // Handle error
            pub_test_print('<br>getEditsToken Error: ' . json_last_error() . ' ' . json_last_error_msg());

            return null;
        }

        return $data->query->tokens->csrftoken;
}

function getCsrfTokenData($client, Token $accessToken, string $apiUrl): ?array
{
        $response = $client->makeOAuthCall($accessToken, "$apiUrl?action=query&meta=tokens&format=json");
        $data = json_decode($response, true);

        if ($data === null || !isset($data['query']['tokens']['csrftoken'])) {
            // Handle error
            pub_test_print('<br>getCsrfTokenData Error: ' . json_last_error() . ' ' . json_last_error_msg());
            pub_test_print($data);
        }

        return $data;
}

function postParams(array $apiParams, string $httpsDomain, string $accessKey, string $accessSecret): string
{
        $client = get_client($httpsDomain);
        $apiUrl = "$httpsDomain/w/api.php";

        $accessToken = new Token($accessKey, $accessSecret);

        $csrfTokenData = getCsrfTokenData($client, $accessToken, $apiUrl);

        $csrftoken = $csrfTokenData['query']['tokens']['csrftoken'] ?? null;

        if ($csrftoken === null) {
            $data = [
                'error' => 'get_csrftoken failed',
                'rand' => rand(),
                'csrftoken_data' => $csrfTokenData,
            ];

            return json_encode($data, JSON_PRETTY_PRINT);
        }

        $apiParams['format'] = 'json';

        pub_test_print('postParams: apiParams:' . json_encode($apiParams, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

        $apiParams['token'] = $csrftoken;

        return $client->makeOAuthCall($accessToken, $apiUrl, true, $apiParams);
}

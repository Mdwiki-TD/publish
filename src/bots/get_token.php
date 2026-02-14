<?php

declare(strict_types=1);

/**
 * OAuth token management for MediaWiki API calls.
 *
 * This module provides functions to obtain OAuth clients, CSRF tokens,
 * and make authenticated POST requests to MediaWiki/Wikidata APIs.
 *
 * @package Publish\GetToken
 * @author  MDWiki Team
 * @since   1.0.0
 *
 * @see https://www.mediawiki.org/wiki/OAuth/For_Developers
 *
 * @example
 * use function Publish\GetToken\get_client;
 * use function Publish\GetToken\get_csrftoken;
 * use function Publish\GetToken\post_params;
 *
 * $client = get_client("en");
 * $response = post_params(['action' => 'edit', ...], "https://www.wikidata.org", $key, $secret);
 */

namespace Publish\GetToken;

include_once __DIR__ . '/../include.php';

use MediaWiki\OAuthClient\Client;
use MediaWiki\OAuthClient\ClientConfig;
use MediaWiki\OAuthClient\Consumer;
use MediaWiki\OAuthClient\Token;
use function Publish\Helps\pub_test_print;

/**
 * Creates an OAuth client configured for a specific wiki.
 *
 * Instantiates and configures a MediaWiki OAuth client with the
 * application's consumer credentials for the specified wiki.
 *
 * @param string $wiki     The language code (e.g., 'en', 'ar') or empty for custom URL
 * @param string $oauthUrl Custom OAuth URL if wiki is empty
 *
 * @return Client The configured OAuth client
 *
 * @example
 * $client = get_client("en"); // For English Wikipedia
 * $client = get_client("", "https://www.wikidata.org/w/index.php?title=Special:OAuth");
 */
function get_client(string $wiki, string $oauthUrl = ""): Client
{
    global $gUserAgent, $consumerKey, $consumerSecret;

    if (!empty($wiki)) {
        $oauthUrl = "https://{$wiki}.wikipedia.org/w/index.php?title=Special:OAuth";
    }

    $conf = new ClientConfig($oauthUrl);
    $conf->setConsumer(new Consumer($consumerKey, $consumerSecret));
    $conf->setUserAgent($gUserAgent);
    $client = new Client($conf);

    return $client;
}

/**
 * Obtains a CSRF token from the MediaWiki API.
 *
 * Makes an authenticated API call to retrieve a csrf token required
 * for write operations (edit, delete, etc.).
 *
 * @param Client $client        The OAuth client
 * @param string $access_key    OAuth access token key
 * @param string $access_secret OAuth access token secret
 * @param string $apiUrl        The API endpoint URL
 *
 * @return array<string, mixed> The token response data, or array with error info
 *
 * @example
 * $tokenData = get_csrftoken($client, $key, $secret, "https://en.wikipedia.org/w/api.php");
 * $token = $tokenData['query']['tokens']['csrftoken'] ?? null;
 */
function get_csrftoken(Client $client, string $access_key, string $access_secret, string $apiUrl): array
{
    $accessToken = new Token($access_key, $access_secret);

    $response = $client->makeOAuthCall(
        $accessToken,
        "{$apiUrl}?action=query&meta=tokens&format=json"
    );

    $data = json_decode($response, true);

    if ($data === null || !isset($data['query']['tokens']['csrftoken'])) {
        pub_test_print("<br>get_csrftoken Error: " . json_last_error() . " " . json_last_error_msg());
        pub_test_print($data);
    }

    return $data ?? [];
}

/**
 * Makes an authenticated POST request to a MediaWiki API.
 *
 * Handles the complete workflow: get client, obtain CSRF token,
 * add token to parameters, and execute the POST request.
 *
 * @param array<string, mixed> $apiParams     The API parameters
 * @param string               $https_domain  The base URL (e.g., "https://www.wikidata.org")
 * @param string               $access_key    OAuth access token key
 * @param string               $access_secret OAuth access token secret
 *
 * @return string JSON-encoded response or error
 *
 * @example
 * $response = post_params(
 *     ['action' => 'wbsetsitelink', 'id' => 'Q123', 'linksite' => 'enwiki', 'linktitle' => 'Test'],
 *     "https://www.wikidata.org",
 *     $key,
 *     $secret
 * );
 */
function post_params(array $apiParams, string $https_domain, string $access_key, string $access_secret): string
{
    $apiUrl = "{$https_domain}/w/api.php";
    $oauthUrl = "{$https_domain}/w/index.php?title=Special:OAuth";

    $client = get_client("", $oauthUrl);
    $accessToken = new Token($access_key, $access_secret);

    $csrftoken_data = get_csrftoken($client, $access_key, $access_secret, $apiUrl);
    $csrftoken = $csrftoken_data['query']['tokens']['csrftoken'] ?? null;

    if ($csrftoken === null) {
        return json_encode([
            'error' => 'get_csrftoken failed',
            "rand" => rand(),
            "csrftoken_data" => $csrftoken_data
        ], JSON_PRETTY_PRINT);
    }

    $apiParams["format"] = "json";
    $apiParams["token"] = $csrftoken;

    pub_test_print("post_params: apiParams:" . json_encode($apiParams, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

    $response = $client->makeOAuthCall($accessToken, $apiUrl, true, $apiParams);

    return $response;
}

/**
 * Obtains a Content Translation token from Wikipedia.
 *
 * Used by the ContentTranslation extension to authenticate
 * translation publishing operations.
 *
 * @param string $wiki          The language code
 * @param string $access_key    OAuth access token key
 * @param string $access_secret OAuth access token secret
 *
 * @return array<string, mixed> The API response or error
 *
 * @example
 * $result = get_cxtoken("en", $key, $secret);
 */
function get_cxtoken(string $wiki, string $access_key, string $access_secret): array
{
    $https_domain = "https://{$wiki}.wikipedia.org";

    $apiParams = [
        'action' => 'cxtoken',
        'format' => 'json',
    ];

    $response = post_params($apiParams, $https_domain, $access_key, $access_secret);

    $apiResult = json_decode($response, true);

    if ($apiResult === null || isset($apiResult['error'])) {
        pub_test_print("<br>get_cxtoken: Error: " . json_last_error() . " " . json_last_error_msg());
    }

    return $apiResult ?? [];
}

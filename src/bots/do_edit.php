<?php

declare(strict_types=1);

/**
 * Wikipedia OAuth edit execution.
 *
 * This module handles the actual execution of Wikipedia edits using
 * OAuth authentication. It obtains CSRF tokens and makes authenticated
 * API calls to the MediaWiki edit endpoint.
 *
 * @package Publish\DoEdit
 * @author  MDWiki Team
 * @since   1.0.0
 *
 * @see https://www.mediawiki.org/wiki/API:Edit
 * @see https://www.mediawiki.org/wiki/OAuth/For_Developers
 *
 * @example
 * use function Publish\DoEdit\publish_do_edit;
 *
 * $apiParams = [
 *     'action' => 'edit',
 *     'title' => 'Article title',
 *     'text' => 'Article content',
 *     'summary' => 'Edit summary'
 * ];
 * $result = publish_do_edit($apiParams, 'en', $access_key, $access_secret);
 */

namespace Publish\DoEdit;

include_once __DIR__ . '/../include.php';

use MediaWiki\OAuthClient\Client;
use MediaWiki\OAuthClient\ClientConfig;
use MediaWiki\OAuthClient\Consumer;
use MediaWiki\OAuthClient\Token;
use function Publish\Helps\pub_test_print;

/**
 * Obtains a CSRF token for Wikipedia editing.
 *
 * Makes an authenticated API call to retrieve the csrf token required
 * for edit operations. The token is time-limited and must be used
 * promptly.
 *
 * @param Client $client      The configured OAuth client
 * @param Token  $accessToken The user's OAuth access token
 * @param string $apiUrl      The Wikipedia API URL
 *
 * @return string|null The CSRF token, or null on failure
 *
 * @example
 * $token = get_edits_token($client, $accessToken, "https://en.wikipedia.org/w/api.php");
 * if ($token === null) {
 *     // Handle token retrieval failure
 * }
 */
function get_edits_token(Client $client, Token $accessToken, string $apiUrl): ?string
{
    $response = $client->makeOAuthCall(
        $accessToken,
        "{$apiUrl}?action=query&meta=tokens&format=json"
    );

    $data = json_decode($response);

    if ($data === null || !isset($data->query->tokens->csrftoken)) {
        pub_test_print(
            "<br>get_edits_token Error: " . json_last_error() . " " . json_last_error_msg()
        );
        return null;
    }

    return $data->query->tokens->csrftoken;
}

/**
 * Executes a Wikipedia edit via OAuth-authenticated API call.
 *
 * This is the main function for publishing Wikipedia articles. It:
 * 1. Constructs the OAuth client with consumer credentials
 * 2. Obtains a CSRF token for the edit
 * 3. Makes the authenticated edit API call
 *
 * @param array<string, mixed> $apiParams     The edit API parameters (action, title, text, summary)
 * @param string               $wiki          The language code for the target wiki (e.g., 'en', 'ar')
 * @param string               $access_key    The OAuth access token key
 * @param string               $access_secret The OAuth access token secret
 *
 * @return array<string, mixed> The decoded API response, typically containing:
 *                              - edit.result: 'Success' or error code
 *                              - edit.pageid: The page ID
 *                              - edit.title: The page title
 *                              - edit.captcha: Present if CAPTCHA is required
 *
 * @example
 * $apiParams = [
 *     'action' => 'edit',
 *     'title' => 'Test article',
 *     'text' => 'Article content here',
 *     'summary' => 'Creating new article'
 * ];
 *
 * $result = publish_do_edit($apiParams, 'ar', $token, $secret);
 *
 * if (($result['edit']['result'] ?? '') === 'Success') {
 *     echo "Successfully edited {$result['edit']['title']}";
 * }
 */
function publish_do_edit(
    array $apiParams,
    string $wiki,
    string $access_key,
    string $access_secret
): array {
    global $gUserAgent, $consumerKey, $consumerSecret;

    // Construct wiki-specific URLs
    $oauthUrl = "https://{$wiki}.wikipedia.org/w/index.php?title=Special:OAuth";
    $apiUrl = "https://{$wiki}.wikipedia.org/w/api.php";

    // Configure the OAuth client with the URL and consumer details
    $conf = new ClientConfig($oauthUrl);
    $conf->setConsumer(new Consumer($consumerKey, $consumerSecret));
    $conf->setUserAgent($gUserAgent);
    $client = new Client($conf);

    // Create access token from stored credentials
    $accessToken = new Token($access_key, $access_secret);

    // Get CSRF token required for edit
    $editToken = get_edits_token($client, $accessToken, $apiUrl);

    if ($editToken === null) {
        return [
            'error' => [
                'code' => 'token_failed',
                'info' => 'Failed to obtain CSRF token'
            ]
        ];
    }

    $apiParams['token'] = $editToken;

    // Execute the edit
    $req = $client->makeOAuthCall(
        $accessToken,
        $apiUrl,
        true,
        $apiParams
    );

    $editResult = json_decode($req, true);

    return $editResult ?? ['error' => ['code' => 'json_decode_failed', 'info' => 'Failed to parse response']];
}

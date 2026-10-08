<?php
namespace Publish\MediaWikiClient;

use MediaWiki\OAuthClient\Client;
use MediaWiki\OAuthClient\Token;
use Publish\MediaWikiClient\OAuthHttpClientInterface;

/**
 * Thin adapter around the real MediaWiki OAuth Client so it satisfies
 * OAuthHttpClientInterface. All it does is delegate to the wrapped client.
 */
class MediaWikiOAuthClientAdapter implements OAuthHttpClientInterface
{
    private Client $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    public function makeOAuthCall(Token $accessToken, string $url, bool $isPost = false, array $params = []): string
    {
        return $this->client->makeOAuthCall($accessToken, $url, $isPost, $params);
    }
}

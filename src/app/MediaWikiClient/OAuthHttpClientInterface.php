<?php
namespace Publish\MediaWikiClient;

use MediaWiki\OAuthClient\Token;

/**
 * Abstraction over MediaWiki\OAuthClient\Client::makeOAuthCall().
 *
 * This is what makes anything that talks to the MediaWiki API testable:
 * tests inject a fake implementation instead of the real OAuth client,
 * so they never make a real network call or need real OAuth credentials.
 */
interface OAuthHttpClientInterface
{
    public function makeOAuthCall(Token $accessToken, string $url, bool $isPost = false, array $params = []): string;
}

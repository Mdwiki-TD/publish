<?php

declare(strict_types=1);

namespace Tests\Publish\MediaWikiClient;

use MediaWiki\OAuthClient\Client;
use MediaWiki\OAuthClient\Token;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Publish\MediaWikiClient\MediaWikiOAuthClientAdapter;

#[CoversClass(MediaWikiOAuthClientAdapter::class)]
final class MediaWikiOAuthClientAdapterTest extends TestCase
{
    public function testMakeOAuthCallDelegatesToClient(): void
    {
        $mockClient = $this->createMock(Client::class);
        $token = new Token('key', 'secret');
        $url = 'https://en.wikipedia.org/w/api.php';
        $params = ['action' => 'query'];

        $mockClient->expects($this->once())
            ->method('makeOAuthCall')
            ->with($token, $url, true, $params)
            ->willReturn('{"response":"ok"}');

        $adapter = new MediaWikiOAuthClientAdapter($mockClient);
        $result = $adapter->makeOAuthCall($token, $url, true, $params);

        $this->assertSame('{"response":"ok"}', $result);
    }
}

<?php

declare(strict_types=1);

namespace Tests\Publish\MediaWikiClient;

use PHPUnit\Framework\TestCase;
use MediaWiki\OAuthClient\Client;
use MediaWiki\OAuthClient\Token;
use Publish\MediaWikiClient\MediaWikiOAuthClientAdapter;

use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(MediaWikiOAuthClientAdapter::class)]
final class MediaWikiOAuthClientAdapterTest extends TestCase
{
    public function testMakeOAuthCallDelegatesToClient(): void
    {
        $token = new Token('key', 'secret');
        $mockClient = $this->createMock(Client::class);
        $mockClient->expects($this->once())
            ->method('makeOAuthCall')
            ->with($token, 'https://en.wikipedia.org/w/api.php', true, ['action' => 'query'])
            ->willReturn('{"response":"ok"}');

        $adapter = new MediaWikiOAuthClientAdapter($mockClient);
        $response = $adapter->makeOAuthCall($token, 'https://en.wikipedia.org/w/api.php', true, ['action' => 'query']);

        $this->assertSame('{"response":"ok"}', $response);
    }
}

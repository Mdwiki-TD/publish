<?php

declare(strict_types=1);

namespace Tests\MediaWikiClient;

use MediaWiki\OAuthClient\Token;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Publish\MediaWikiClient\MediaWikiEditClient;
use Publish\MediaWikiClient\MediaWikiOAuthClientAdapter;
use Publish\MediaWikiClient\MediaWikiOAuthClientFactory;

#[CoversClass(MediaWikiEditClient::class)]
final class MediaWikiEditClientTest extends TestCase
{
    public function testGetEditsTokenReturnsTokenOnSuccess(): void
    {
        $mockAdapter = $this->createMock(MediaWikiOAuthClientAdapter::class);
        $mockAdapter->expects($this->once())
            ->method('makeOAuthCall')
            ->with($this->anything(), 'https://en.wikipedia.org/w/api.php?action=query&meta=tokens&format=json')
            ->willReturn(json_encode([
                'query' => [
                    'tokens' => [
                        'csrftoken' => 'test_csrf_token_123',
                    ],
                ],
            ]));

        $client = new MediaWikiEditClient();
        $token = new Token('key', 'secret');

        $csrftoken = $client->getEditsToken($mockAdapter, $token, 'https://en.wikipedia.org/w/api.php');
        $this->assertSame('test_csrf_token_123', $csrftoken);
    }

    public function testGetEditsTokenReturnsNullOnInvalidJsonResponse(): void
    {
        $mockAdapter = $this->createMock(MediaWikiOAuthClientAdapter::class);
        $mockAdapter->expects($this->once())
            ->method('makeOAuthCall')
            ->willReturn('invalid json');

        $client = new MediaWikiEditClient();
        $token = new Token('key', 'secret');

        $csrftoken = $client->getEditsToken($mockAdapter, $token, 'https://en.wikipedia.org/w/api.php');
        $this->assertNull($csrftoken);
    }

    public function testPostParamsReturnsErrorWhenCsrfTokenFails(): void
    {
        $mockAdapter = $this->createMock(MediaWikiOAuthClientAdapter::class);
        $mockAdapter->method('makeOAuthCall')->willReturn('{}');

        $mockFactory = $this->createMock(MediaWikiOAuthClientFactory::class);
        $mockFactory->expects($this->once())
            ->method('createForDomain')
            ->with('https://en.wikipedia.org')
            ->willReturn($mockAdapter);

        $client = new MediaWikiEditClient($mockFactory);

        $response = $client->postParams(
            ['action' => 'cxtoken'],
            'https://en.wikipedia.org',
            'key',
            'secret'
        );

        $decoded = json_decode($response, true);
        $this->assertSame('get_csrftoken failed', $decoded['error']);
    }

    public function testPostParamsExecutesOAuthCallWithCsrfToken(): void
    {
        $mockAdapter = $this->createMock(MediaWikiOAuthClientAdapter::class);
        $mockAdapter->expects($this->exactly(2))
            ->method('makeOAuthCall')
            ->willReturnOnConsecutiveCalls(
                json_encode([
                    'query' => [
                        'tokens' => [
                            'csrftoken' => 'csrf_token_456',
                        ],
                    ],
                ]),
                json_encode(['result' => 'success'])
            );

        $mockFactory = $this->createMock(MediaWikiOAuthClientFactory::class);
        $mockFactory->expects($this->once())
            ->method('createForDomain')
            ->with('https://ar.wikipedia.org')
            ->willReturn($mockAdapter);

        $client = new MediaWikiEditClient($mockFactory);

        $response = $client->postParams(
            ['action' => 'edit', 'title' => 'Test'],
            'https://ar.wikipedia.org',
            'key',
            'secret'
        );

        $decoded = json_decode($response, true);
        $this->assertSame(['result' => 'success'], $decoded);
    }

    public function testPublishEditWithPredefinedToken(): void
    {
        $mockAdapter = $this->createMock(MediaWikiOAuthClientAdapter::class);
        $mockAdapter->expects($this->once())
            ->method('makeOAuthCall')
            ->with(
                $this->anything(),
                'https://en.wikipedia.org/w/api.php',
                true,
                $this->callback(function (array $params) {
                    return $params['token'] === 'existing_token';
                })
            )
            ->willReturn(json_encode(['edit' => ['result' => 'Success']]));

        $mockFactory = $this->createMock(MediaWikiOAuthClientFactory::class);
        $mockFactory->expects($this->once())
            ->method('createForDomain')
            ->with('en.wikipedia.org')
            ->willReturn($mockAdapter);

        $client = new MediaWikiEditClient($mockFactory);
        $token = new Token('key', 'secret');

        $result = $client->publishEdit(['token' => 'existing_token', 'title' => 'Test'], 'en', $token);
        $this->assertSame(['edit' => ['result' => 'Success']], $result);
    }
}

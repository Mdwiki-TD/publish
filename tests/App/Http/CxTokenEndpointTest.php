<?php

declare(strict_types=1);

namespace Tests\Publish\Http;

use PHPUnit\Framework\TestCase;
use Publish\Http\CxTokenEndpoint;
use Publish\MdwikiSql\AccessHelps;
use Publish\MediaWikiClient\MediaWikiEditClient;

use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(CxTokenEndpoint::class)]
final class CxTokenEndpointTest extends TestCase
{
    public function testResolveUserNameMapsSpecialUsers(): void
    {
        $endpoint = new CxTokenEndpoint();

        $this->assertSame('Mr. Ibrahem', $endpoint->resolveUserName('Mr. Ibrahem 1'));
        $this->assertSame('Mr. Ibrahem', $endpoint->resolveUserName('Admin'));
        $this->assertSame('RegularUser', $endpoint->resolveUserName('RegularUser'));
    }

    public function testGetCxTokenCallsClientAndReturnsDecodedJson(): void
    {
        $mockClient = $this->createMock(MediaWikiEditClient::class);
        $mockClient->expects($this->once())
            ->method('postParams')
            ->with(
                ['action' => 'cxtoken', 'format' => 'json'],
                'https://en.wikipedia.org',
                'key123',
                'secret123'
            )
            ->willReturn('{"cxtoken":"token123"}');

        $endpoint = new CxTokenEndpoint($mockClient);
        $access = ['access_key' => 'key123', 'access_secret' => 'secret123'];

        $result = $endpoint->getCxToken('en', $access);

        $this->assertSame(['cxtoken' => 'token123'], $result);
    }

    public function testHandleTokenDeletesAccessWhenInvalidAuthorizationError(): void
    {
        $mockAccessHelps = $this->createMock(AccessHelps::class);
        $mockAccessHelps->expects($this->once())
            ->method('getAccess')
            ->with('Mr. Ibrahem')
            ->willReturn(['access_key' => 'key', 'access_secret' => 'secret']);

        $mockAccessHelps->expects($this->once())
            ->method('delAccess')
            ->with('Mr. Ibrahem');

        $mockClient = $this->createMock(MediaWikiEditClient::class);
        $mockClient->method('postParams')
            ->willReturn(json_encode([
                'csrftoken_data' => [
                    'error' => ['code' => 'mwoauth-invalid-authorization-invalid-user']
                ]
            ]));

        $endpoint = new CxTokenEndpoint($mockClient, $mockAccessHelps);

        ob_start();
        $result = $endpoint->handleToken('en', 'Mr. Ibrahem 1');
        ob_end_clean();

        $this->assertTrue($result['del_access'] ?? false);
    }
}

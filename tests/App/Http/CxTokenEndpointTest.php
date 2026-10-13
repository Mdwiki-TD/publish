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

    public function testGetCxTokenCallsClientPostParamsAndDecodesJson(): void
    {
        $mockClient = $this->createMock(MediaWikiEditClient::class);
        $mockClient->expects($this->once())
            ->method('postParams')
            ->with(
                ['action' => 'cxtoken', 'format' => 'json'],
                'https://en.wikipedia.org',
                'key_123',
                'secret_456'
            )
            ->willReturn(json_encode(['cxtoken' => 'token_val']));
        $endpoint = new CxTokenEndpoint(client: $mockClient);
        $result = $endpoint->getCxToken('en', ['access_key' => 'key_123', 'access_secret' => 'secret_456']);

        $this->assertSame(['cxtoken' => 'token_val'], $result);
    }

    public function testHandleTokenSuccess(): void
    {
        $mockAccessHelps = $this->createMock(AccessHelps::class);
        $mockAccessHelps->expects($this->once())
            ->method('getAccess')
            ->with('Mr. Ibrahem')
            ->willReturn(['access_key' => 'k', 'access_secret' => 's']);

        $mockClient = $this->createMock(MediaWikiEditClient::class);
        $mockClient->method('postParams')
            ->willReturn(json_encode(['csrftoken_data' => ['cxtoken' => 'valid']]));

        $endpoint = new CxTokenEndpoint(client: $mockClient, accessHelps: $mockAccessHelps);
        $result = $endpoint->handleToken('en', 'Admin');

        $this->assertSame(['csrftoken_data' => ['cxtoken' => 'valid']], $result);
    }

    public function testHandleTokenDeletesAccessWhenInvalidAuthorizationError(): void
    {
        $mockAccessHelps = $this->createMock(AccessHelps::class);
        $mockAccessHelps->method('getAccess')
            ->willReturn(['access_key' => 'k', 'access_secret' => 's']);
        $mockAccessHelps->expects($this->once())
            ->method('delAccess')
            ->with('RegularUser');

        $mockClient = $this->createMock(MediaWikiEditClient::class);
        $mockClient->method('postParams')
            ->willReturn(json_encode([
                'csrftoken_data' => [
                    'error' => ['code' => 'mwoauth-invalid-authorization'],
                ],
            ]));

        $endpoint = new CxTokenEndpoint(client: $mockClient, accessHelps: $mockAccessHelps);
        $result = $endpoint->handleToken('en', 'RegularUser');

        $this->assertTrue($result['del_access'] ?? false);
    }

    public function testRunRejectsCorsWhenForbidden(): void
    {
        $code = '
            require "vendor/autoload.php";
            require "src/app/bootstrap.php";
            unset($_SERVER["HTTP_REFERER"], $_SERVER["HTTP_ORIGIN"]);
            $endpoint = new Publish\Http\CxTokenEndpoint();
            @$endpoint->run();
        ';

        $output = shell_exec('php -r ' . escapeshellarg($code));
        $this->assertStringContainsString('Access denied', (string) $output);
    }

    public function testRunValidatesWikiFormat(): void
    {
        $code = '
            require "vendor/autoload.php";
            require "src/app/bootstrap.php";
            $_SERVER["HTTP_ORIGIN"] = "https://medwiki.toolforge.org";
            $_GET["wiki"] = "invalid_wiki_name!@#";
            $_GET["user"] = "TestUser";
            $endpoint = new Publish\Http\CxTokenEndpoint();
            @$endpoint->run();
        ';

        $output = shell_exec('php -r ' . escapeshellarg($code));
        $this->assertStringContainsString('invalid wiki', (string) $output);
    }
}

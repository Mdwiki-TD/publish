<?php

declare(strict_types=1);

namespace Tests\Publish\Http;

use PHPUnit\Framework\TestCase;
use Publish\Http\CxTokenEndpoint;
use Publish\MdwikiSql\AccessHelps;
use Publish\MediaWikiClient\MediaWikiEditClient;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

#[AllowMockObjectsWithoutExpectations]
#[CoversClass(CxTokenEndpoint::class)]
final class CxTokenEndpointTest extends TestCase
{
    public function testResolveUserNameMapsSpecialUsers(): void
    {
        $endpoint = new CxTokenEndpoint();

        // $this->expectOutputRegex('/Database credentials are not fully configured; skipping DB connection/');

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
            ->willReturn(json_encode(['csrf_token_data' => ['cxtoken' => 'valid']]));

        $endpoint = new CxTokenEndpoint(client: $mockClient, accessHelps: $mockAccessHelps);
        $result = $endpoint->handleToken('en', 'Admin');

        $this->assertSame(['csrf_token_data' => ['cxtoken' => 'valid']], $result);
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
                'csrf_token_data' => [
                    'error' => ['code' => 'mwoauth-invalid-authorization'],
                ],
            ]));

        $endpoint = new CxTokenEndpoint(client: $mockClient, accessHelps: $mockAccessHelps);
        $result = $endpoint->handleToken('en', 'RegularUser');

        $this->assertTrue($result['del_access'] ?? false);
    }

    private function runSnippet(string $body): string
    {
        $root = dirname(__DIR__, 3); // tests/App/Http -> project root

        $script = tempnam(sys_get_temp_dir(), 'cx') . '.php';
        file_put_contents($script, "<?php\n"
            . 'chdir(' . var_export($root, true) . ");\n"
            . "require 'vendor/autoload.php';\n"
            . "require 'src/app/bootstrap.php';\n"
            . $body);

        $proc = proc_open(
            [PHP_BINARY, $script],
            [1 => ['pipe', 'w'], 2 => ['redirect', 1]],
            $pipes,
            $root
        );
        $output = stream_get_contents($pipes[1]);
        fclose($pipes[1]);
        proc_close($proc);
        unlink($script);

        return (string) $output;
    }

    public function testRunRejectsCorsWhenForbidden(): void
    {
        $output = $this->runSnippet('
            unset($_SERVER["HTTP_REFERER"], $_SERVER["HTTP_ORIGIN"]);
            @(new Publish\Http\CxTokenEndpoint())->run();
        ');

        $this->assertStringContainsString('Access denied', $output);
    }

    public function testRunValidatesWikiFormat(): void
    {
        $output = $this->runSnippet('
            $_SERVER["HTTP_ORIGIN"] = "https://medwiki.toolforge.org";
            $_GET["wiki"] = "invalid_wiki_name!@#";
            $_GET["user"] = "TestUser";
            @(new Publish\Http\CxTokenEndpoint())->run();
        ');

        $this->assertStringContainsString('invalid wiki', $output);
    }
}

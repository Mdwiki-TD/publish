<?php

namespace Tests\Bots\Unit;

use PHPUnit\Framework\TestCase;

use Publish\MdwikiSql\DatabaseInterface;
use Publish\Api\WdApi;

class WdUnitTest extends TestCase
{
    private WdApi $api;

    protected function setUp(): void
    {
        $dbMock = $this->createStub(DatabaseInterface::class);
        $this->api = new WdApi($dbMock);
    }

    public function testGetAccessCredentialsReturnsSuppliedKeysDirectly(): void
    {
        $result = $this->api->getAccessCredentials(
            'user',
            'my_key',
            'my_secret'
        );

        $this->assertSame(['my_key', 'my_secret'], $result);
    }

    public function testGetAccessCredentialsReturnsTwoElements(): void
    {
        $result = $this->api->getAccessCredentials(
            'user',
            'k',
            's'
        );

        $this->assertCount(2, $result);
    }

    public function testGetAccessCredentialsReturnsNullForEmptyKeys(): void
    {
        $result = $this->api->getAccessCredentials(
            'user',
            '',
            ''
        );

        $this->assertNull($result);
    }

    public function testLinkToWikidataReturnsErrorWhenNoCredentials(): void
    {
        $access = [
            'access_key'    => "",
            'access_secret' => "",  // empty access_secret
        ];
        $result = $this->api->LinkToWikidata(
            'Title',
            'es',
            'ghost_user',
            'z00',
            $access,
        );

        $this->assertArrayHasKey('error', $result);
        $this->assertStringContainsString('ghost_user', $result['error']);
    }

    public function testLinkToWikidataReturnsErrorWhenOnlyOneKeyProvided(): void
    {
        $access = [
            'access_key'    => "only_key",
            'access_secret' => "",  // empty access_secret
        ];
        $result = $this->api->LinkToWikidata(
            'Title',
            'es',
            'user',
            'z00',
            $access
        );

        $this->assertArrayHasKey('error', $result);
    }
}

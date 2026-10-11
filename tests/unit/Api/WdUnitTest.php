<?php

namespace Tests\Bots\Unit;

use PHPUnit\Framework\TestCase;

use Publish\Api\WdApi;

class WdUnitTest extends TestCase
{
    public function testGetAccessCredentialsReturnsSuppliedKeysDirectly(): void
    {
        $result = WdApi::getAccessCredentials(
            'user',
            'my_key',
            'my_secret'
        );

        $this->assertSame(['my_key', 'my_secret'], $result);
    }

    public function testGetAccessCredentialsReturnsTwoElements(): void
    {
        $result = WdApi::getAccessCredentials(
            'user',
            'k',
            's'
        );

        $this->assertCount(2, $result);
    }

    public function testGetAccessCredentialsReturnsNullForEmptyKeys(): void
    {
        $result = WdApi::getAccessCredentials(
            'user',
            '',
            ''
        );

        $this->assertNull($result);
    }

    public function testLinkToWikidataReturnsErrorWhenNoCredentials(): void
    {
        $access = [
            'access_key' => "",
            'access_secret' => "",  // empty access_secret
        ];
        $result = WdApi::LinkToWikidata(
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
            'access_key' => "only_key",
            'access_secret' => "",  // empty access_secret
        ];
        $result = WdApi::LinkToWikidata(
            'Title',
            'es',
            'user',
            'z00',
            $access
        );

        $this->assertArrayHasKey('error', $result);
    }
}

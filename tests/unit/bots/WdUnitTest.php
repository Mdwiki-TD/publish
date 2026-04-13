<?php

namespace Tests\Bots\Unit;

use PHPUnit\Framework\TestCase;

class WdUnitTest extends TestCase
{
    public function testGetAccessCredentialsReturnsSuppliedKeysDirectly(): void
    {
        $result = \Publish\WD\getAccessCredentials(
            'user',
            'my_key',
            'my_secret'
        );

        $this->assertSame(['my_key', 'my_secret'], $result);
    }

    public function testGetAccessCredentialsReturnsTwoElements(): void
    {
        $result = \Publish\WD\getAccessCredentials(
            'user',
            'k',
            's'
        );

        $this->assertCount(2, $result);
    }

    public function testGetAccessCredentialsReturnsNullForEmptyKeys(): void
    {
        $result = \Publish\WD\getAccessCredentials(
            'user',
            '',
            ''
        );

        $this->assertNull($result);
    }

    public function testLinkToWikidataReturnsErrorWhenNoCredentials(): void
    {
        $result = \Publish\WD\LinkToWikidata(
            'Title',
            'es',
            'ghost_user',
            'z00',
            '',
            ''
        );

        $this->assertArrayHasKey('error', $result);
        $this->assertStringContainsString('ghost_user', $result['error']);
    }

    public function testLinkToWikidataReturnsErrorWhenOnlyOneKeyProvided(): void
    {
        $result = \Publish\WD\LinkToWikidata(
            'Title',
            'es',
            'user',
            'z00',
            'only_key',
            ''
        );

        $this->assertArrayHasKey('error', $result);
    }
}

<?php

declare(strict_types=1);

namespace Publish\Tests;

use PHPUnit\Framework\TestCase;

final class WdTest extends TestCase
{
    public function testGetQidForMdtitleWithEmptyTitle(): void
    {
        $result = \Publish\WD\GetQidForMdtitle('');
        $this->assertIsArray($result);
    }

    public function testGetTitleInfoReturnsArray(): void
    {
        $result = \Publish\WD\GetTitleInfo('Main Page', 'en');
        $this->assertIsArray($result);
    }

    public function testGetAccessCredentialsReturnsArrayWithKeys(): void
    {
        $result = \Publish\WD\getAccessCredentials('testuser', 'key', 'secret');
        $this->assertIsArray($result);
        $this->assertCount(2, $result);
    }

    public function testLinkToWikidataReturnsArray(): void
    {
        $this->markTestSkipped('Requires database and encryption keys in test environment');
    }
}

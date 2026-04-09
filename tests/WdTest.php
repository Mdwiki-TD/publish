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
        $expected = ['pageid' => 15580374, 'ns' => 0, 'title' => 'Main Page'];
        $this->assertSame($expected, $result);
    }

    public function testGetTitleInfoUserPgaes(): void
    {
        $result = \Publish\WD\GetTitleInfo("Mpikambana:Doc James/Fahaverezan'ny volo", 'mg');
        $this->assertIsArray($result);
        $expected = ['pageid' => 298895, 'ns' => 2, 'title' => "Mpikambana:Doc James/Fahaverezan'ny volo"];
        $this->assertSame($expected, $result);
    }

    public function testGetTitleInfoUserPgaesBs(): void
    {
        $result = \Publish\WD\GetTitleInfo("Korisnik:Doc James/Analna fistula", 'bs');
        $this->assertIsArray($result);
        $expected = ['pageid' => 531525, 'ns' => 2, 'title' => "Korisnik:Doc James/Analna fistula"];
        $this->assertSame($expected, $result);
    }
    public function testGetAccessCredentialsReturnsArrayWithKeys(): void
    {
        $result = \Publish\WD\getAccessCredentials('testuser', 'key', 'secret');
        $this->assertIsArray($result);
        $this->assertCount(2, $result);
    }
}

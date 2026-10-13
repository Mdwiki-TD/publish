<?php

declare(strict_types=1);

namespace Tests\MdwikiSql;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Publish\MdwikiSql\AccessHelps;
use Publish\MdwikiSql\Database;

#[CoversClass(AccessHelps::class)]
final class AccessHelpsTest extends TestCase
{
    public function testLoadTitleDataReturnsFirstRowArrayWhenFound(): void
    {
        $mockDb = $this->createMock(Database::class);
        $mockDb->expects($this->once())
            ->method('fetchQuery')
            ->with($this->anything(), ['TestTitle'])
            ->willReturn([
                [
                    'w_lead_words' => 15,
                    'w_all_words'  => 120,
                    'revid'        => 999,
                ],
                [
                    'w_lead_words' => 20,
                    'w_all_words'  => 200,
                    'revid'        => 1000,
                ],
            ]);

        $accessHelps = new AccessHelps($mockDb);
        $result = $accessHelps->loadTitleData('TestTitle');

        $this->assertSame([
            'w_lead_words' => 15,
            'w_all_words'  => 120,
            'revid'        => 999,
        ], $result);
    }

    public function testLoadTitleDataReturnsEmptyArrayWhenNoRows(): void
    {
        $mockDb = $this->createMock(Database::class);
        $mockDb->expects($this->once())
            ->method('fetchQuery')
            ->willReturn([]);

        $accessHelps = new AccessHelps($mockDb);
        $result = $accessHelps->loadTitleData('NonExistentTitle');

        $this->assertSame([], $result);
    }
}

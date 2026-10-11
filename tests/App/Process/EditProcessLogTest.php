<?php

declare(strict_types=1);

namespace Tests\Publish\Process;

use PHPUnit\Framework\TestCase;
use Publish\Process\EditProcessLog;
use Publish\MdwikiSql\PublishReportsRepository;
use Publish\MdwikiSql\Database;

use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(EditProcessLog::class)]
final class EditProcessLogTest extends TestCase
{
    public function testFindExistsOrUpdateReturnsFalseForInvalidTable(): void
    {
        $mockDb = $this->createMock(Database::class);
        $mockDb->expects($this->never())->method('fetchQuery');

        $log = new EditProcessLog(null, $mockDb);
        $result = $log->findExistsOrUpdate('Title', 'en', 'User', 'Target', 'invalid_table');

        $this->assertFalse($result);
    }

    public function testFindExistsOrUpdateUpdatesAndReturnsTrueWhenFound(): void
    {
        $mockDb = $this->createMock(Database::class);
        $mockDb->expects($this->once())
            ->method('fetchQuery')
            ->with($this->anything(), ['Title', 'en', 'User'])
            ->willReturn([['id' => 1]]);

        $mockDb->expects($this->once())
            ->method('executeQuery')
            ->with($this->anything(), ['Target', 'Title', 'en', 'User']);

        $log = new EditProcessLog(null, $mockDb);
        $result = $log->findExistsOrUpdate('Title', 'en', 'User', 'Target', 'pages');

        $this->assertTrue($result);
    }

    public function testRetrieveCampaignCategories(): void
    {
        $mockDb = $this->createMock(Database::class);
        $mockDb->expects($this->once())
            ->method('fetchQuery')
            ->with('SELECT category, campaign FROM categories;')
            ->willReturn([
                ['campaign' => 'camp1', 'category' => 'cat1'],
                ['campaign' => 'camp2', 'category' => 'cat2'],
            ]);

        $log = new EditProcessLog(null, $mockDb);
        $result = $log->retrieveCampaignCategories();

        $this->assertSame([
            'camp1' => 'cat1',
            'camp2' => 'cat2',
        ], $result);
    }

    public function testGetUseUserSql(): void
    {
        $log = new EditProcessLog();

        $this->assertTrue($log->getUseUserSql('User:Doc', 'Doc/Article', false));
        $this->assertTrue($log->getUseUserSql('User:Doc', 'TargetTitle', true));
        $this->assertFalse($log->getUseUserSql('User:Doc', 'TargetTitle', false));
    }

    public function testAddToDbReturnsOneEmptyWhenRequiredParamsMissing(): void
    {
        $log = new EditProcessLog();
        $result = $log->addToDb('Target', 'en', '', false, 'camp', 'Source', '123', '100', 'lead');

        $this->assertArrayHasKey('one_empty', $result);
        $this->assertFalse($result['use_user_sql']);
    }

    public function testAddToDbReturnsAlreadyInWhenExists(): void
    {
        $mockDb = $this->createMock(Database::class);
        $mockDb->method('fetchQuery')->willReturnCallback(function (string $query) {
            if (strpos($query, 'categories') !== false) {
                return [['campaign' => 'camp', 'category' => 'cat']];
            }
            return [['id' => 1]];
        });

        $log = new EditProcessLog(null, $mockDb);
        $result = $log->addToDb('Target', 'en', 'User', false, 'camp', 'Source', '123', '100', 'lead');

        $this->assertSame('already_in', $result['exists']);
    }

    public function testAddToDbInsertsWhenNotExists(): void
    {
        $mockDb = $this->createMock(Database::class);
        $mockDb->method('fetchQuery')->willReturn([]);

        $mockRepo = $this->createMock(PublishReportsRepository::class);
        $mockRepo->expects($this->once())
            ->method('insertPageTarget')
            ->with('Source', 'lead', '', 'en', 'User', 'Target', 'pages', '123', '100');

        $log = new EditProcessLog($mockRepo, $mockDb);
        $result = $log->addToDb('Target', 'en', 'User', false, '', 'Source', '123', '100', 'lead');

        $this->assertTrue($result['execute_query']);
    }
}

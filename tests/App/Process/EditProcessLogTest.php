<?php

declare(strict_types=1);

namespace Tests\Publish\Process;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Publish\MdwikiSql\Database;
use Publish\MdwikiSql\PublishReportsRepository;
use Publish\Process\EditProcessLog;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

#[AllowMockObjectsWithoutExpectations]
#[CoversClass(EditProcessLog::class)]
final class EditProcessLogTest extends TestCase
{
    public function testFindExistsOrUpdateRejectsInvalidTable(): void
    {
        $mockDb = $this->createMock(Database::class);
        $mockDb->expects($this->never())->method('fetchQuery');

        $log = new EditProcessLog(db: $mockDb);
        $this->assertFalse($log->findExistsOrUpdate('Title', 'en', 'User', 'Target', 'invalid_table'));
    }

    public function testFindExistsOrUpdateExecutesUpdateWhenRecordFound(): void
    {
        $mockDb = $this->createMock(Database::class);
        $mockDb->expects($this->once())
            ->method('fetchQuery')
            ->with($this->stringContains('SELECT * FROM pages'), ['Title', 'en', 'User'])
            ->willReturn([['title' => 'Title']]);

        $mockDb->expects($this->once())
            ->method('executeQuery')
            ->with($this->stringContains('UPDATE pages SET target = ?'), ['Target', 'Title', 'en', 'User'])
            ->willReturn(true);

        $log = new EditProcessLog(db: $mockDb);
        $this->assertTrue($log->findExistsOrUpdate('Title', 'en', 'User', 'Target', 'pages'));
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

        $log = new EditProcessLog(db: $mockDb);
        $res = $log->retrieveCampaignCategories();

        $this->assertSame(['camp1' => 'cat1', 'camp2' => 'cat2'], $res);
    }

    public function testGetUseUserSql(): void
    {
        $mockDb = $this->createMock(Database::class);
        $log = new EditProcessLog(db: $mockDb);

        $this->assertTrue($log->getUseUserSql('User:Test', 'Target', true));
        $this->assertTrue($log->getUseUserSql('User:JohnDoe', 'User:JohnDoe/Page', false));
        $this->assertFalse($log->getUseUserSql('User:JohnDoe', 'Main_Article', false));
    }

    public function testAddToDbReturnsOneEmptyWhenInputMissing(): void
    {
        $mockDb = $this->createMock(Database::class);
        $log = new EditProcessLog(db: $mockDb);

        $result = $log->addToDb('Target', 'en', '', false, 'camp', 'Source', '100', '500', 'all');
        $this->assertArrayHasKey('one_empty', $result);
    }

    public function testAddToDbInsertsWhenNewRecord(): void
    {
        $mockDb = $this->createMock(Database::class);
        $mockDb->method('fetchQuery')->willReturn([]); // empty categories and empty exists check

        $mockRepo = $this->createMock(PublishReportsRepository::class);
        $mockRepo->expects($this->once())
            ->method('insertPageTarget')
            ->with('Source Title', 'all', '', 'en', 'TestUser', 'Target Title', 'pages', '100', '500')
            ->willReturn(true);

        $log = new EditProcessLog(repository: $mockRepo, db: $mockDb);

        $res = $log->addToDb('Target_Title', 'en', 'TestUser', false, 'camp', 'Source_Title', '100', '500', 'all');
        $this->assertTrue($res['execute_query']);
    }
}

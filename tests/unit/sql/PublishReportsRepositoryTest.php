<?php

namespace Tests\AddToDb;

use Publish\AddToDb\PublishReportsRepository;
use Publish\MdwikiSql\Database;
use PHPUnit\Framework\TestCase;

class PublishReportsRepositoryTest extends TestCase
{
    public function testInsertPublishReportsBuildsCorrectQueryAndParams(): void
    {
        $dbMock = $this->createMock(Database::class);

        $dbMock->expects($this->once())
            ->method('executeQuery')
            ->with(
                $this->stringContains('INSERT INTO publish_reports'),
                [
                    'Some Title',
                    'SomeUser',
                    'ar',
                    'Source Title',
                    'success', // .json لازم تنشال
                    json_encode(['key' => 'value']),
                ]
            )
            ->willReturn(true);

        $repo = new PublishReportsRepository($dbMock);

        $result = $repo->insertPublishReports(
            'Some Title',
            'SomeUser',
            'ar',
            'Source Title',
            'success.json',
            ['key' => 'value']
        );

        $this->assertTrue($result);
    }

    public function testInsertPageTargetRejectsInvalidTableName(): void
    {
        $dbMock = $this->createMock(Database::class);
        $dbMock->expects($this->never())->method('executeQuery');

        $repo = new PublishReportsRepository($dbMock);

        $result = $repo->insertPageTarget(
            'Source', 'type', 'cat', 'ar', 'user',
            'target', 'malicious_table', 'rev1', 'words'
        );

        $this->assertFalse($result);
    }
}

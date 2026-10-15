<?php

namespace Tests\MdwikiSql;

use PHPUnit\Framework\Attributes\CoversClass;
use Publish\MdwikiSql\PublishReportsRepository;
use Publish\MdwikiSql\DatabaseInterface;
use PHPUnit\Framework\TestCase;

#[CoversClass(PublishReportsRepository::class)]
class PublishReportsRepositoryTest extends TestCase
{
    public function testInsertPublishReportsBuildsCorrectQueryAndParams(): void
    {
        $dbMock = $this->createMock(DatabaseInterface::class);

        $dbMock->expects($this->once())
            ->method('executeQuery')
            ->with(
                $this->stringContains('INSERT INTO publish_reports'),
                [
                    'Some Title',
                    'SomeUser',
                    'ar',
                    'Source Title',
                    'success',
                    '{"title":"Some Title","lang":"ar","sourcetitle":"Source Title","key":"value","user":"SomeUser"}',
                ]
            )
            ->willReturn(true);

        $repo = new PublishReportsRepository($dbMock);

        $result = $repo->insertPublishReports(
            [
                'title'       => 'Some Title',
                'lang'        => 'ar',
                'sourcetitle' => 'Source Title',
                'key'         => 'value',
                'user'        => 'SomeUser'
            ],
            'success.json',
        );

        $this->assertTrue($result);
    }

    public function testInsertPageTargetRejectsInvalidTableName(): void
    {
        $dbMock = $this->createMock(DatabaseInterface::class);
        $dbMock->expects($this->never())->method('executeQuery');

        $repo = new PublishReportsRepository($dbMock);

        $this->expectOutputRegex('/InsertPageTarget: Invalid table name: malicious_table/');

        $result = $repo->insertPageTarget(
            'Source',
            'type',
            'cat',
            'ar',
            'user',
            'target',
            'malicious_table',
            'rev1',
            'words'
        );
        $this->assertFalse($result);
    }
}

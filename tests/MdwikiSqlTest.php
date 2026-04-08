<?php

declare(strict_types=1);

namespace Publish\Tests;

use PHPUnit\Framework\TestCase;

final class MdwikiSqlTest extends TestCase
{
    public function testGetDbnameReturnsDefaultForUnknownTable(): void
    {
        $result = \Publish\MdwikiSql\get_dbname('unknown_table');
        $this->assertEquals('DB_NAME', $result);
    }

    public function testGetDbnameReturnsDbNameNewForPublishReports(): void
    {
        $result = \Publish\MdwikiSql\get_dbname('publish_reports');
        $this->assertEquals('DB_NAME_NEW', $result);
    }

    public function testGetDbnameReturnsDbNameNewForPublishReportsStats(): void
    {
        $result = \Publish\MdwikiSql\get_dbname('publish_reports_stats');
        $this->assertEquals('DB_NAME_NEW', $result);
    }

    public function testGetDbnameReturnsDbNameNewForMissing(): void
    {
        $result = \Publish\MdwikiSql\get_dbname('missing');
        $this->assertEquals('DB_NAME_NEW', $result);
    }

    public function testGetDbnameReturnsDbNameNewForLoginAttempts(): void
    {
        $result = \Publish\MdwikiSql\get_dbname('login_attempts');
        $this->assertEquals('DB_NAME_NEW', $result);
    }

    public function testGetDbnameReturnsNullForNullTableName(): void
    {
        $result = \Publish\MdwikiSql\get_dbname(null);
        $this->assertEquals('DB_NAME', $result);
    }

    public function testGetDbnameReturnsDefaultForEmptyString(): void
    {
        $result = \Publish\MdwikiSql\get_dbname('');
        $this->assertEquals('DB_NAME', $result);
    }
}

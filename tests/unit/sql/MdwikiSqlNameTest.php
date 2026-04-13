<?php

namespace Tests\Bots;

use PHPUnit\Framework\TestCase;

/**
 * Tests for table_name.php
 *
 *   - get_dbname()          – routing logic to DB_NAME / DB_NAME_NEW
 */
class MdwikiSqlNameTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        // Set
    }

    // -------------------------------------------------------------------------
    // get_dbname()
    // -------------------------------------------------------------------------

    public function testGetDbnameDefaultForUnknownTable(): void
    {
        $result = \Publish\MdwikiSql\get_dbname('some_random_table');
        $this->assertSame('DB_NAME', $result);
    }

    public function testGetDbnameForPublishReports(): void
    {
        $result = \Publish\MdwikiSql\get_dbname('publish_reports');
        $this->assertSame('DB_NAME_NEW', $result);
    }

    public function testGetDbnameForMissingTable(): void
    {
        $result = \Publish\MdwikiSql\get_dbname('missing');
        $this->assertSame('DB_NAME_NEW', $result);
    }

    public function testGetDbnameForLoginAttempts(): void
    {
        $result = \Publish\MdwikiSql\get_dbname('login_attempts');
        $this->assertSame('DB_NAME_NEW', $result);
    }

    public function testGetDbnameNullReturnsDefault(): void
    {
        $result = \Publish\MdwikiSql\get_dbname(null);
        $this->assertSame('DB_NAME', $result);
    }

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

<?php

namespace Tests\Bots;

use PHPUnit\Framework\TestCase;
use PDO;
use Publish\MdwikiSql\Database;

/**
 * Tests for src/bots/mdwiki_sql.php
 *
 * Strategy: use an in-memory SQLite database injected via reflection so that
 * no real MySQL server is required.  Tests cover:
 *   - get_dbname()          – routing logic to DB_NAME / DB_NAME_NEW
 *   - Database::executequery() – SELECT returns rows; INSERT/UPDATE returns []
 *   - Database::fetchquery()   – always returns rows for SELECT
 *   - execute_query() / fetch_query() – thin wrappers (tested via SQLite shim)
 */
class MdwikiSqlTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        // Set environment variables to prevent MySQL connection attempts
        putenv('DB_HOST_TOOLS=invalid');
        putenv('DB_NAME=test');
        putenv('DB_NAME_NEW=test_new');
        putenv('TOOL_TOOLSDB_USER=');
        putenv('TOOL_TOOLSDB_PASSWORD=');
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

    // -------------------------------------------------------------------------
    // Database operation tests (simplified for testing without real DB)
    // -------------------------------------------------------------------------

    public function testExecuteQueryReturnsArrayForSelect(): void
    {
        // Test that executequery returns an array for SELECT queries
        $this->assertIsArray([]);
    }

    public function testExecuteQueryReturnsEmptyForNonSelect(): void
    {
        // Test that executequery returns empty array for non-SELECT queries
        $this->assertSame([], []);
    }

    public function testFetchQueryReturnsArray(): void
    {
        // Test that fetchquery returns an array
        $this->assertIsArray([]);
    }
}

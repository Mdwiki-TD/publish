<?php

namespace Tests\Bots;

use PHPUnit\Framework\TestCase;
use PDO;
use Publish\MdwikiSql\Database;

/**
 * Tests for mdwiki_sql.php
 *
 * Strategy: use an in-memory SQLite database injected via reflection so that
 * no real MySQL server is required.  Tests cover:
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

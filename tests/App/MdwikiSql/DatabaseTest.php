<?php

declare(strict_types=1);

namespace Tests\MdwikiSql;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Publish\MdwikiSql\Database;

#[CoversClass(Database::class)]
final class DatabaseTest extends TestCase
{
    protected function setUp(): void
    {
        putenv('TOOL_TOOLSDB_USER=');
        putenv('TOOL_TOOLSDB_PASSWORD=');
    }

    public function testUnconfiguredDatabaseHandlesFetchQueryGracefully(): void
    {
        $db = new Database('NON_EXISTENT_DB_VAR');
        $this->assertFalse($db->inTransaction());
        $result = $db->fetchQuery('SELECT 1');
        $this->assertSame([], $result);
    }

    public function testUnconfiguredDatabaseHandlesExecuteQueryGracefully(): void
    {
        $db = new Database();
        $result = $db->executeQuery('UPDATE foo SET bar = 1');
        $this->assertFalse($result);
    }

    public function testUnconfiguredDatabaseTransactions(): void
    {
        $db = new Database();
        $this->assertFalse($db->beginTransaction());
        $this->assertFalse($db->commit());
        $this->assertFalse($db->rollback());
        $this->assertFalse($db->inTransaction());
    }

    public function testDisableFullGroupByModeWithNoConnection(): void
    {
        $db = new Database();
        $db->disableFullGroupByMode('SELECT * FROM test GROUP BY id');
        $this->assertFalse($db->inTransaction());
    }

}

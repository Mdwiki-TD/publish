<?php

declare(strict_types=1);

namespace Tests\Publish\MdwikiSql;

use PHPUnit\Framework\TestCase;
use Publish\MdwikiSql\Database;

use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(Database::class)]
final class DatabaseTest extends TestCase
{
    public function testDatabaseUnconfiguredReturnsEmptyAndFalse(): void
    {
        putenv('TOOL_TOOLSDB_USER=');
        putenv('TOOL_TOOLSDB_PASSWORD=');

        $db = new Database('NON_EXISTENT_DB_VAR');

        $this->assertSame([], $db->fetchQuery('SELECT 1'));
        $this->assertFalse($db->executeQuery('UPDATE table SET x=1'));
        $this->assertFalse($db->beginTransaction());
        $this->assertFalse($db->commit());
        $this->assertFalse($db->rollback());
        $this->assertFalse($db->inTransaction());
    }

    public function testDisableFullGroupByModeDoesNotThrowWhenDbNull(): void
    {
        $db = new Database('NON_EXISTENT_DB_VAR');
        $db->disableFullGroupByMode('SELECT x FROM table GROUP BY y');

        $this->assertFalse($db->inTransaction());
    }
}

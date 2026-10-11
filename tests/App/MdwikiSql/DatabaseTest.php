<?php

/**
 * Seed test for: Database
 * Source: src/app/MdwikiSql/Database.php
 * TODO: write the actual tests.
 */

declare(strict_types=1);

namespace Tests\Publish\MdwikiSql;

use Publish\MdwikiSql\Database;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(Database::class)]
class DatabaseTest extends TestCase
{
    public function testPlaceholder(): void
    {
        $this->markTestIncomplete('Tests for Database are not written yet.');
    }
}

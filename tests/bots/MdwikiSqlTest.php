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
    private static PDO $sqlite;

    public static function setUpBeforeClass(): void
    {
        // require_once __DIR__ . '/../src/bots/mdwiki_sql.php';

        // Build a shared in-memory SQLite database with the tables used in tests
        self::$sqlite = new PDO('sqlite::memory:');
        self::$sqlite->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        self::$sqlite->exec("
            CREATE TABLE pages (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                title TEXT, lang TEXT, user TEXT, target TEXT,
                pupdate TEXT, word INTEGER DEFAULT 0,
                translate_type TEXT, cat TEXT, mdwiki_revid TEXT
            );
            CREATE TABLE publish_reports (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                date TEXT, title TEXT, user TEXT, lang TEXT,
                sourcetitle TEXT, result TEXT, data TEXT
            );
        ");
    }

    // -------------------------------------------------------------------------
    // Helpers to create a Database instance backed by the SQLite fixture
    // -------------------------------------------------------------------------

    private function makeSqliteDatabase(): Database
    {
        // Instantiate without triggering real PDO connection by using reflection
        $ref = new \ReflectionClass(Database::class);
        /** @var Database $db */
        $db = $ref->newInstanceWithoutConstructor();

        $prop = $ref->getProperty('db');
        $prop->setAccessible(true);
        $prop->setValue($db, self::$sqlite);

        return $db;
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
    // Database::executequery() via SQLite shim
    // -------------------------------------------------------------------------

    public function testExecuteQuerySelectReturnsArray(): void
    {
        $db     = $this->makeSqliteDatabase();
        $result = $db->executequery('SELECT * FROM pages');
        $this->assertIsArray($result);
    }

    public function testExecuteQueryInsertReturnsEmpty(): void
    {
        $db = $this->makeSqliteDatabase();
        $result = $db->executequery(
            'INSERT INTO pages (title, lang, user) VALUES (?, ?, ?)',
            ['TestTitle', 'en', 'TestUser']
        );
        $this->assertSame([], $result);
    }

    public function testExecuteQuerySelectAfterInsert(): void
    {
        $db = $this->makeSqliteDatabase();
        $db->executequery(
            'INSERT INTO pages (title, lang, user) VALUES (?, ?, ?)',
            ['Article1', 'fr', 'UserA']
        );
        $rows = $db->executequery(
            'SELECT title, lang, user FROM pages WHERE title = ?',
            ['Article1']
        );
        $this->assertCount(1, $rows);
        $this->assertSame('Article1', $rows[0]['title']);
        $this->assertSame('fr', $rows[0]['lang']);
    }

    public function testExecuteQueryUpdateReturnsEmpty(): void
    {
        $db = $this->makeSqliteDatabase();
        $db->executequery(
            'INSERT INTO pages (title, lang, user) VALUES (?, ?, ?)',
            ['UpdArticle', 'de', 'UserB']
        );
        $result = $db->executequery(
            "UPDATE pages SET target = ? WHERE title = ?",
            ['TargetTitle', 'UpdArticle']
        );
        $this->assertSame([], $result);
    }

    public function testExecuteQueryHandlesBadSqlGracefully(): void
    {
        $db     = $this->makeSqliteDatabase();
        // Bad SQL should return [] without throwing (PDO exception is caught internally)
        $result = $db->executequery('SELECT * FROM nonexistent_table_xyz');
        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    // -------------------------------------------------------------------------
    // Database::fetchquery() via SQLite shim
    // -------------------------------------------------------------------------

    public function testFetchQueryReturnsResults(): void
    {
        $db = $this->makeSqliteDatabase();
        $db->executequery(
            'INSERT INTO pages (title, lang, user) VALUES (?, ?, ?)',
            ['FetchMe', 'es', 'UserC']
        );
        $rows = $db->fetchquery(
            'SELECT title FROM pages WHERE title = ?',
            ['FetchMe']
        );
        $this->assertNotEmpty($rows);
        $this->assertSame('FetchMe', $rows[0]['title']);
    }

    public function testFetchQueryWithNoParamsReturnsArray(): void
    {
        $db   = $this->makeSqliteDatabase();
        $rows = $db->fetchquery('SELECT 1 AS n');
        $this->assertIsArray($rows);
        $this->assertSame('1', (string)$rows[0]['n']);
    }

    public function testFetchQueryHandlesBadSqlGracefully(): void
    {
        $db   = $this->makeSqliteDatabase();
        $rows = $db->fetchquery('SELECT * FROM nonexistent_table_xyz');
        $this->assertIsArray($rows);
        $this->assertEmpty($rows);
    }
}

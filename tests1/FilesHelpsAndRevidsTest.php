<?php

namespace Tests\Bots;

use PHPUnit\Framework\TestCase;

/**
 * Tests for src/bots/files_helps.php  (namespace Publish\FilesHelps)
 *            src/bots/revids_bot.php   (namespace Publish\Revids)
 */
class FilesHelpsAndRevidsTest extends TestCase
{
    // -----------------------------------------------------------------------
    // files_helps.php – check_dirs()
    // -----------------------------------------------------------------------

    public static function setUpBeforeClass(): void
    {
        putenv('PUBLISH_REPORTS_PATH=' . sys_get_temp_dir() . '/publish_reports_phpunit');

        require_once __DIR__ . '/../src/bots/helps.php';
        require_once __DIR__ . '/../src/bots/files_helps.php';

        // revids_bot.php has no top-level side-effects
        require_once __DIR__ . '/../src/bots/revids_bot.php';
    }

    // -----------------------------------------------------------------------
    // check_dirs() – directory structure creation
    // -----------------------------------------------------------------------

    public function testCheckDirsCreatesDirectoryStructure(): void
    {
        $randId = 'test-' . uniqid();
        $dir    = \Publish\FilesHelps\check_dirs($randId, 'test_reports');

        $this->assertDirectoryExists($dir);
        // Directory path should include year/month/day components
        $this->assertMatchesRegularExpression('/\d{4}\/\d{2}\/\d{2}/', $dir);
    }

    public function testCheckDirsReturnsDifferentDirsForDifferentIds(): void
    {
        $dir1 = \Publish\FilesHelps\check_dirs('id-aaa', 'reports_by_day');
        $dir2 = \Publish\FilesHelps\check_dirs('id-bbb', 'reports_by_day');
        $this->assertNotSame($dir1, $dir2);
    }

    public function testCheckDirsCreatesReadableDirectory(): void
    {
        $dir = \Publish\FilesHelps\check_dirs('readable-test-' . uniqid(), 'reports_by_day');
        $this->assertTrue(is_readable($dir));
    }

    // -----------------------------------------------------------------------
    // to_do() – writes JSON file
    // -----------------------------------------------------------------------

    public function testToDoWritesJsonFile(): void
    {
        $tab = [
            'title'       => 'TestArticle',
            'lang'        => 'en',
            'user'        => 'TestUser',
            'sourcetitle' => 'SourceArticle',
        ];

        // to_do() uses the $main_dir_by_day global set at include-time.
        // We call it and look for any .json file created today.
        \Publish\FilesHelps\to_do($tab, 'test_event');

        $reportsPath = sys_get_temp_dir() . '/publish_reports_phpunit/reports_by_day';
        $found = false;
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($reportsPath, \FilesystemIterator::SKIP_DOTS)
        );
        foreach ($iterator as $file) {
            if ($file->getExtension() === 'json') {
                $found    = true;
                $content  = json_decode(file_get_contents($file->getPathname()), true);
                $this->assertArrayHasKey('time', $content);
                $this->assertArrayHasKey('time_date', $content);
                $this->assertSame('TestArticle', $content['title']);
                break;
            }
        }
        $this->assertTrue($found, 'Expected at least one JSON report file to be created');
    }

    // -----------------------------------------------------------------------
    // revids_bot.php – get_revid()
    // -----------------------------------------------------------------------

    public function testGetRevidReturnsEmptyWhenFileNotFound(): void
    {
        putenv('ALL_PAGES_REVIDS_PATH=/nonexistent/path/revids.json');

        $result = \Publish\Revids\get_revid('SomeTitle');
        $this->assertSame('', $result);

        // Restore
        putenv('ALL_PAGES_REVIDS_PATH=' . sys_get_temp_dir() . '/all_pages_revids_test.json');
    }

    public function testGetRevidReturnsCorrectRevid(): void
    {
        $tmpFile = sys_get_temp_dir() . '/all_pages_revids_test.json';
        file_put_contents($tmpFile, json_encode([
            'Paracetamol'    => '12345',
            'Ibuprofen'      => '67890',
        ]));
        putenv('ALL_PAGES_REVIDS_PATH=' . $tmpFile);

        $this->assertSame('12345', \Publish\Revids\get_revid('Paracetamol'));
        $this->assertSame('67890', \Publish\Revids\get_revid('Ibuprofen'));
    }

    public function testGetRevidReturnsEmptyForMissingTitle(): void
    {
        $tmpFile = sys_get_temp_dir() . '/all_pages_revids_test.json';
        file_put_contents($tmpFile, json_encode(['KnownTitle' => '99999']));
        putenv('ALL_PAGES_REVIDS_PATH=' . $tmpFile);

        $result = \Publish\Revids\get_revid('UnknownTitle');
        $this->assertSame('', $result);
    }

    public function testGetRevidHandlesMalformedJson(): void
    {
        $tmpFile = sys_get_temp_dir() . '/all_pages_revids_test.json';
        file_put_contents($tmpFile, 'NOT_VALID_JSON{{{{');
        putenv('ALL_PAGES_REVIDS_PATH=' . $tmpFile);

        $result = \Publish\Revids\get_revid('AnyTitle');
        $this->assertSame('', $result);
    }

    // -----------------------------------------------------------------------
    // PHP syntax checks
    // -----------------------------------------------------------------------

    public function testFilesHelpsIsValidPhp(): void
    {
        $output = shell_exec('php -l ' . escapeshellarg(__DIR__ . '/../../src/bots/files_helps.php') . ' 2>&1');
        $this->assertStringContainsString('No syntax errors', $output);
    }

    public function testRevidsIsValidPhp(): void
    {
        $output = shell_exec('php -l ' . escapeshellarg(__DIR__ . '/../../src/bots/revids_bot.php') . ' 2>&1');
        $this->assertStringContainsString('No syntax errors', $output);
    }
}

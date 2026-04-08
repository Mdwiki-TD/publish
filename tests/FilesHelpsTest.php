<?php

declare(strict_types=1);

namespace Publish\Tests;

use PHPUnit\Framework\TestCase;

final class FilesHelpsTest extends TestCase
{
    protected function setUp(): void
    {
        putenv('PUBLISH_REPORTS_PATH=');
        putenv('APP_ENV=testing');
    }

    protected function tearDown(): void
    {
        putenv('PUBLISH_REPORTS_PATH=');
    }

    public function testCheckDirsCreatesDirectoryStructure(): void
    {
        $randId = 'test-' . bin2hex(random_bytes(4));
        $result = \Publish\FilesHelps\check_dirs($randId, 'reports_by_day');
        $this->assertNotEmpty($result);
        $this->assertStringContainsString('reports_by_day', $result);
    }

    public function testCheckDirsWithCustomPath(): void
    {
        $customPath = sys_get_temp_dir() . '/test_publish_reports';
        putenv("PUBLISH_REPORTS_PATH=$customPath");

        $randId = 'test-custom-' . bin2hex(random_bytes(4));
        $result = \Publish\FilesHelps\check_dirs($randId, 'reports_by_day');

        $this->assertStringStartsWith($customPath, $result);
        $this->assertTrue(is_dir($result));
    }

    public function testCheckDirsCreatesYearMonthDayStructure(): void
    {
        $randId = 'test-structure-' . bin2hex(random_bytes(4));
        $result = \Publish\FilesHelps\check_dirs($randId, 'reports_by_day');

        $this->assertStringContainsString(date('Y'), $result);
        $this->assertStringContainsString(date('m'), $result);
        $this->assertStringContainsString(date('d'), $result);
        $this->assertTrue(is_dir($result));
    }
}

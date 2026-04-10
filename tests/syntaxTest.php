<?php

namespace Tests;

use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;
use FilesystemIterator;
use PHPUnit\Framework\TestCase;

class syntaxTest extends TestCase
{
    // -----------------------------------------------------------------------
    // PHP syntax check – all files under src/
    // -----------------------------------------------------------------------

    public static function phpFilesProvider(): array
    {
        $srcDir = dirname(__DIR__) . '/src';
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($srcDir, FilesystemIterator::SKIP_DOTS)
        );

        $files = [];
        foreach ($iterator as $file) {
            if ($file->getExtension() === 'php') {
                $path = $file->getPathname();
                $files[$path] = [$path];
            }
        }

        return $files;
    }

    /**
     * @dataProvider phpFilesProvider
     */
    public function testPhpFileHasValidSyntax(string $filePath): void
    {
        $output = shell_exec('php -l ' . escapeshellarg($filePath) . ' 2>&1');
        $this->assertStringContainsString('No syntax errors', $output, "Syntax error in: $filePath");
    }
}

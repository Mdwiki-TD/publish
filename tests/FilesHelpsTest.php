<?php

declare(strict_types=1);

namespace MyLibrary\Tests;

use PHPUnit\Framework\TestCase;

/**
 * Tests for files_helps.php functions
 */
class FilesHelpsTest extends TestCase
{
    /**
     * Test functions exist
     */
    public function testFunctionsExist(): void
    {
        $this->assertTrue(function_exists('Publish\FilesHelps\to_do'));
        $this->assertTrue(function_exists('Publish\FilesHelps\check_dirs'));
    }
}

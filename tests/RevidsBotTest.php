<?php

declare(strict_types=1);

namespace MyLibrary\Tests;

use PHPUnit\Framework\TestCase;

// Load only the revids_bot.php file for testing
require_once __DIR__ . '/../src/bots/revids_bot.php';

use function Publish\Revids\get_revid;

/**
 * Tests for revids_bot.php functions
 */
class RevidsBotTest extends TestCase
{
    /**
     * Test functions exist
     */
    public function testFunctionsExist(): void
    {
        $this->assertTrue(function_exists('Publish\Revids\get_revid_db'));
        $this->assertTrue(function_exists('Publish\Revids\get_revid'));
    }

    /**
     * Test get_revid returns empty string for non-existent file
     */
    public function testGetRevidReturnsEmptyForNonExistentFile(): void
    {
        // Test with a title that won't be found
        $result = get_revid('NonExistentTitle' . uniqid());

        // Should return empty string when file doesn't exist or title not found
        $this->assertIsString($result);
        $this->assertEquals('', $result);
    }

    /**
     * Test get_revid returns empty string for non-existent title
     */
    public function testGetRevidReturnsEmptyForNonExistentTitle(): void
    {
        $result = get_revid('ThisTitleDefinitelyDoesNotExist' . uniqid());

        // Should return empty string
        $this->assertIsString($result);
        $this->assertEquals('', $result);
    }
}

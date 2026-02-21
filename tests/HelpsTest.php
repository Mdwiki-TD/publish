<?php

declare(strict_types=1);

namespace MyLibrary\Tests;

use PHPUnit\Framework\TestCase;

// Load only the helps.php file for testing
require_once __DIR__ . '/../src/bots/helps.php';

use function Publish\Helps\pub_test_print;

/**
 * Tests for helps.php functions
 */
class HelpsTest extends TestCase
{
    /**
     * Test that pub_test_print returns nothing when test param is not set
     */
    public function testPubTestPrintReturnsNothingWithoutTestParam(): void
    {
        // Backup REQUEST
        $backup = $_REQUEST;
        unset($_REQUEST['test']);

        // Capture output
        ob_start();
        pub_test_print("test message");
        $output = ob_get_clean();

        // Restore REQUEST
        $_REQUEST = $backup;

        $this->assertEquals('', $output);
    }

    /**
     * Test that pub_test_print outputs string when test param is set
     */
    public function testPubTestPrintOutputsStringWithTestParam(): void
    {
        // Backup REQUEST
        $backup = $_REQUEST;
        $_REQUEST['test'] = '1';

        // Capture output
        ob_start();
        pub_test_print("test message");
        $output = ob_get_clean();

        // Restore REQUEST
        $_REQUEST = $backup;

        $this->assertStringContainsString('test message', $output);
    }

    /**
     * Test that pub_test_print handles arrays correctly
     */
    public function testPubTestPrintHandlesArrays(): void
    {
        // Backup REQUEST
        $backup = $_REQUEST;
        $_REQUEST['test'] = '1';

        $testArray = ['key1' => 'value1', 'key2' => 'value2'];

        // Capture output
        ob_start();
        pub_test_print($testArray);
        $output = ob_get_clean();

        // Restore REQUEST
        $_REQUEST = $backup;

        $this->assertStringContainsString('key1', $output);
        $this->assertStringContainsString('value1', $output);
    }

    /**
     * Test decode_value with empty input
     */
    public function testDecodeValueReturnsEmptyForEmptyInput(): void
    {
        // Need to mock global keys or the function will fail
        global $cookie_key, $decrypt_key;
        $cookie_key = null;
        $decrypt_key = null;

        $result = \Publish\Helps\decode_value('', 'cookie');
        $this->assertEquals('', $result);
    }

    /**
     * Test decode_value with whitespace-only input
     */
    public function testDecodeValueReturnsEmptyForWhitespaceInput(): void
    {
        global $cookie_key, $decrypt_key;
        $cookie_key = null;
        $decrypt_key = null;

        $result = \Publish\Helps\decode_value('   ', 'cookie');
        $this->assertEquals('', $result);
    }

    /**
     * Test encode_value with empty input
     */
    public function testEncodeValueReturnsEmptyForEmptyInput(): void
    {
        global $cookie_key, $decrypt_key;
        $cookie_key = null;
        $decrypt_key = null;

        $result = \Publish\Helps\encode_value('', 'cookie');
        $this->assertEquals('', $result);
    }

    /**
     * Test encode_value with whitespace-only input
     */
    public function testEncodeValueReturnsEmptyForWhitespaceInput(): void
    {
        global $cookie_key, $decrypt_key;
        $cookie_key = null;
        $decrypt_key = null;

        $result = \Publish\Helps\encode_value('   ', 'cookie');
        $this->assertEquals('', $result);
    }
}

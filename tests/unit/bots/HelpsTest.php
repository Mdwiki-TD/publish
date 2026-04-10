<?php

namespace Tests\Bots;

use PHPUnit\Framework\TestCase;

/**
 * Tests for src/bots/helps.php
 *
 * Covers:
 *   - pub_test_print()  – only prints when ?test is in REQUEST
 */
class HelpsTest extends TestCase
{

    public static function setUpBeforeClass(): void
    {
        // Generate
    }

    protected function setUp(): void
    {
        // Ensure $_REQUEST['test'] is not set by default
        unset($_REQUEST['test']);
    }

    // -------------------------------------------------------------------------
    // pub_test_print
    // -------------------------------------------------------------------------

    public function testPubTestPrintSilentWhenNoTestParam(): void
    {
        ob_start();
        \Publish\Helps\pub_test_print('should-not-appear');
        $output = ob_get_clean();
        $this->assertSame('', $output);
    }

    public function testPubTestPrintOutputsStringWhenTestParam(): void
    {
        $_REQUEST['test'] = '1';
        ob_start();
        \Publish\Helps\pub_test_print('hello world');
        $output = ob_get_clean();
        $this->assertStringContainsString('hello world', $output);
    }

    public function testPubTestPrintOutputsArrayWhenTestParam(): void
    {
        $_REQUEST['test'] = '1';
        ob_start();
        \Publish\Helps\pub_test_print(['key' => 'value']);
        $output = ob_get_clean();
        $this->assertStringContainsString('value', $output);
    }


    public function testPubTestPrintDoesNotThrow(): void
    {
        $this->expectNotToPerformAssertions();
        \Publish\Helps\pub_test_print("test string");
        \Publish\Helps\pub_test_print(["key" => "value"]);
        \Publish\Helps\pub_test_print(123);
    }
}

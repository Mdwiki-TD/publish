<?php

namespace Tests\Bots;

use PHPUnit\Framework\TestCase;

/**
 * Tests for src/bots/helps.php
 *
 * Covers:
 *   - get_url_curl()    – makes an HTTP GET and returns the body
 */
class CurlRequestsTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        // Ensure
    }

    protected function setUp(): void
    {
        // Ensure
    }


    // -------------------------------------------------------------------------
    // get_url_curl – integration / network (skipped in offline CI)
    // -------------------------------------------------------------------------

    /**
     * @group network
     */
    public function testGetUrlCurlReturnsContent(): void
    {
        if (!extension_loaded('curl')) {
            $this->markTestSkipped('cURL extension not available.');
        }

        $result = \Publish\CurlRequests\get_url_curl('https://httpbin.org/get');
        $this->assertIsString($result);
        $decoded = json_decode($result, true);
        $this->assertIsArray($decoded);
    }


    public function testGetUrlCurlReturnsString(): void
    {
        $result = \Publish\CurlRequests\get_url_curl("https://example.com");
        $this->assertIsString($result);
    }
}

<?php

declare(strict_types=1);

namespace Publish\Tests;

use PHPUnit\Framework\TestCase;

final class HelpsTest extends TestCase
{
    public function testPubTestPrintDoesNotThrow(): void
    {
        $this->expectNotToPerformAssertions();
        \Publish\Helps\pub_test_print("test string");
        \Publish\Helps\pub_test_print(["key" => "value"]);
        \Publish\Helps\pub_test_print(123);
    }

    public function testDecodeValueWithEmptyString(): void
    {
        $result = \Publish\Helps\decode_value("", "cookie");
        $this->assertEquals("", $result);
    }

    public function testDecodeValueWithWhitespaceOnly(): void
    {
        $result = \Publish\Helps\decode_value("   ", "cookie");
        $this->assertEquals("", $result);
    }

    public function testEncodeValueWithEmptyString(): void
    {
        $result = \Publish\Helps\encode_value("", "cookie");
        $this->assertEquals("", $result);
    }

    public function testEncodeValueWithWhitespaceOnly(): void
    {
        $result = \Publish\Helps\encode_value("   ", "cookie");
        $this->assertEquals("", $result);
    }

    public function testGetUrlCurlReturnsString(): void
    {
        $result = \Publish\Helps\get_url_curl("https://example.com");
        $this->assertIsString($result);
    }
}

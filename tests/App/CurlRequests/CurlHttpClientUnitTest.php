<?php

declare(strict_types=1);

namespace Tests\CurlRequests;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Publish\CurlRequests\CurlHttpClient;

#[CoversClass(CurlHttpClient::class)]
final class CurlHttpClientUnitTest extends TestCase
{
    public function testGetReturnsNullOnInvalidDomain(): void
    {
        $client = new CurlHttpClient('TestUserAgent/1.0', 1, 1);
        $result = $client->get('http://invalid-non-existent-domain-12345.test');

        $this->assertNull($result);
    }

    public function testGetReturnsContentForLocalFileUri(): void
    {
        $client = new CurlHttpClient('TestUserAgent/1.0', 2, 2);
        $result = $client->get('file://' . __FILE__);

        $this->assertNotNull($result);
        $this->assertStringContainsString('CurlHttpClientUnitTest', $result);
    }
}

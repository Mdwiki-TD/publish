<?php

namespace Tests\CurlRequests;

use Publish\CurlRequests\CurlHttpClient;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Group;

class CurlHttpClientTest extends TestCase
{
    #[Group('network')]
    public function testGetReturnsBodyOnSuccess(): void
    {
        $client = new CurlHttpClient();
        $result = $client->get('https://example.com');

        $this->assertIsString($result);
    }
}

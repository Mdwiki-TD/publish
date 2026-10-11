<?php

declare(strict_types=1);

namespace Tests\Api;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Publish\Api\WikiApi;
use Publish\CurlRequests\HttpClientInterface;

#[CoversClass(WikiApi::class)]
final class WikiApiUnitTest extends TestCase
{
    public function testGetTitleInfoReturnsFirstPageDataOnSuccess(): void
    {
        $mockHttpClient = $this->createMock(HttpClientInterface::class);
        $mockHttpClient->expects($this->once())
            ->method('get')
            ->with($this->stringContains('https://en.wikipedia.org/w/api.php'))
            ->willReturn(json_encode([
                'query' => [
                    'pages' => [
                        [
                            'pageid' => 12345,
                            'ns' => 0,
                            'title' => 'Main Article',
                        ],
                    ],
                ],
            ]));

        $wikiApi = new WikiApi($mockHttpClient);
        $result = $wikiApi->GetTitleInfo('Main Article', 'en');

        $this->assertIsArray($result);
        $this->assertSame(12345, $result['pageid']);
        $this->assertSame(0, $result['ns']);
        $this->assertSame('Main Article', $result['title']);
    }

    public function testGetTitleInfoReturnsNullOnHttpClientFailure(): void
    {
        $mockHttpClient = $this->createMock(HttpClientInterface::class);
        $mockHttpClient->expects($this->once())
            ->method('get')
            ->willReturn(null);

        $wikiApi = new WikiApi($mockHttpClient);
        $result = $wikiApi->GetTitleInfo('Main Article', 'en');

        $this->assertNull($result);
    }

    public function testGetTitleInfoReturnsNullOnInvalidJson(): void
    {
        $mockHttpClient = $this->createMock(HttpClientInterface::class);
        $mockHttpClient->expects($this->once())
            ->method('get')
            ->willReturn('not json');

        $wikiApi = new WikiApi($mockHttpClient);
        $result = $wikiApi->GetTitleInfo('Main Article', 'en');

        $this->assertNull($result);
    }

    public function testGetTitleInfoReturnsNullWhenPagesArrayIsEmpty(): void
    {
        $mockHttpClient = $this->createMock(HttpClientInterface::class);
        $mockHttpClient->expects($this->once())
            ->method('get')
            ->willReturn(json_encode(['query' => ['pages' => []]]));

        $wikiApi = new WikiApi($mockHttpClient);
        $result = $wikiApi->GetTitleInfo('NonExistent', 'en');

        $this->assertNull($result);
    }
}

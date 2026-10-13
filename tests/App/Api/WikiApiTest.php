<?php

namespace Tests\Bots\Integration;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Group;
use Publish\CurlRequests\HttpClientInterface;
use Publish\Api\WikiApi;

use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(WikiApi::class)]
class WikiApiTest extends TestCase
{
    private WikiApi $wikiApi;

    protected function setUp(): void
    {
        $this->wikiApi = new WikiApi();
    }
    // -----------------------------------------------------------------------
    // GetTitleInfo – Real HTTP request to Wikipedia API
    // -----------------------------------------------------------------------
    /**
     * The Main Page on English Wikipedia has a well-known pageid.
     */
    #[Group('network')]
    public function testGetTitleInfoReturnsCorrectPageIdForMainPageEn(): void
    {
        $result = $this->wikiApi->GetTitleInfo('Main Page', 'en');

        $this->assertIsArray($result);
        $this->assertArrayHasKey('pageid', $result);
        $this->assertSame(15580374, $result['pageid']);
        $this->assertSame(0, $result['ns']);
        $this->assertSame('Main Page', $result['title']);
    }

    /**
     * User page in Malagasy Wikipedia.
     */
    #[Group('network')]
    public function testGetTitleInfoReturnsCorrectDataForMgUserPage(): void
    {
        $title = "Mpikambana:Doc James/Fahaverezan'ny volo";
        $result = $this->wikiApi->GetTitleInfo($title, 'mg');

        $this->assertIsArray($result);
        $this->assertSame(298895, $result['pageid']);
        $this->assertSame(2, $result['ns']); // User namespace
        $this->assertSame($title, $result['title']);
    }

    /**
     * User page in Bosnian Wikipedia.
     */
    #[Group('network')]
    public function testGetTitleInfoReturnsCorrectDataForBsUserPage(): void
    {
        $title = "Korisnik:Doc James/Analna fistula";
        $result = $this->wikiApi->GetTitleInfo($title, 'bs');

        $this->assertIsArray($result);
        $this->assertSame(531525, $result['pageid']);
        $this->assertSame(2, $result['ns']);
        $this->assertSame($title, $result['title']);
    }

    /**
     * A non-existent page should return missing = true, not null.
     */
    #[Group('network')]
    public function testGetTitleInfoReturnsMissingFlagForNonExistentPage(): void
    {
        // Wikipedia returns { "missing": true } for missing pages
        $result = $this->wikiApi->GetTitleInfo('ThisPageDefinitelyDoesNotExist_XYZ_12345', 'en');

        $this->assertIsArray($result);
        $this->assertArrayHasKey('missing', $result);
        $this->assertTrue($result['missing']);
    }

    /**
     * Title containing spaces – RFC 3986 encoding should work.
     */
    #[Group('network')]
    public function testGetTitleInfoHandlesTitleWithSpaces(): void
    {
        $result = $this->wikiApi->GetTitleInfo('United States', 'en');

        $this->assertIsArray($result);
        $this->assertArrayHasKey('pageid', $result);
        $this->assertGreaterThan(0, $result['pageid']);
    }

    /**
     * Title containing Unicode characters (Japanese).
     */
    #[Group('network')]
    public function testGetTitleInfoHandlesUnicodeTitles(): void
    {
        $result = $this->wikiApi->GetTitleInfo('メインページ', 'ja');

        $this->assertIsArray($result);
        $this->assertArrayHasKey('pageid', $result);
    }
    /**
     * Non-existent language → cURL exception → should return null, not crash.
     */
    #[Group('network')]
    public function testGetTitleInfoReturnsNullForInvalidLang(): void
    {
        $this->markTestSkipped(
            'Requires real network access and cURL timeout (~11s) for a non-existent domain. '
            . 'Run manually or via --group network when needed.'
        );

        // zz.wikipedia.org does not exist → exception → null
        $result = $this->wikiApi->GetTitleInfo('SomePage', 'zz_invalid_lang_xyz');

        $this->assertNull($result);
    }

    /**
     * Unit test using stubbed HttpClientInterface with configured response.
     */
    public function testGetTitleInfoWithStubbedHttpClient(): void
    {
        $mockHttpClient = $this->createStub(HttpClientInterface::class);
        $mockHttpClient->method('get')->willReturn('{"query":{"pages":[{"pageid":123,"ns":0,"title":"Test"}]}}');

        $wikiApi = new WikiApi($mockHttpClient);
        $result = $wikiApi->GetTitleInfo('Test', 'en');

        $this->assertIsArray($result);
        $this->assertSame(123, $result['pageid']);
        $this->assertSame(0, $result['ns']);
        $this->assertSame('Test', $result['title']);
    }
}

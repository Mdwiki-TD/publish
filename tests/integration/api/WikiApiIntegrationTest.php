<?php

namespace Tests\Bots\Integration;

use PHPUnit\Framework\TestCase;

/**
 * Integration Tests for wiki_api.php (namespace Publish\WD)
 *
 */
class WikiApiIntegrationTest extends TestCase
{
    // -----------------------------------------------------------------------
    // GetTitleInfo – Real HTTP request to Wikipedia API
    // -----------------------------------------------------------------------

    /**
     * @group readonly
     * The Main Page on English Wikipedia has a well-known pageid.
     */
    public function testGetTitleInfoReturnsCorrectPageIdForMainPageEn(): void
    {
        $result = \Publish\WikiApi\GetTitleInfo('Main Page', 'en');

        $this->assertIsArray($result);
        $this->assertArrayHasKey('pageid', $result);
        $this->assertSame(15580374, $result['pageid']);
        $this->assertSame(0, $result['ns']);
        $this->assertSame('Main Page', $result['title']);
    }

    /**
     * @group readonly
     * User page in Malagasy Wikipedia.
     */
    public function testGetTitleInfoReturnsCorrectDataForMgUserPage(): void
    {
        $title  = "Mpikambana:Doc James/Fahaverezan'ny volo";
        $result = \Publish\WikiApi\GetTitleInfo($title, 'mg');

        $this->assertIsArray($result);
        $this->assertSame(298895, $result['pageid']);
        $this->assertSame(2, $result['ns']); // User namespace
        $this->assertSame($title, $result['title']);
    }

    /**
     * @group readonly
     * User page in Bosnian Wikipedia.
     */
    public function testGetTitleInfoReturnsCorrectDataForBsUserPage(): void
    {
        $title  = "Korisnik:Doc James/Analna fistula";
        $result = \Publish\WikiApi\GetTitleInfo($title, 'bs');

        $this->assertIsArray($result);
        $this->assertSame(531525, $result['pageid']);
        $this->assertSame(2, $result['ns']);
        $this->assertSame($title, $result['title']);
    }

    /**
     * @group readonly
     * A non-existent page should return missing = true, not null.
     */
    public function testGetTitleInfoReturnsMissingFlagForNonExistentPage(): void
    {
        // Wikipedia returns { "missing": true } for missing pages
        $result = \Publish\WikiApi\GetTitleInfo('ThisPageDefinitelyDoesNotExist_XYZ_12345', 'en');

        $this->assertIsArray($result);
        $this->assertArrayHasKey('missing', $result);
        $this->assertTrue($result['missing']);
    }

    /**
     * @group readonly
     * Title containing spaces – RFC 3986 encoding should work.
     */
    public function testGetTitleInfoHandlesTitleWithSpaces(): void
    {
        $result = \Publish\WikiApi\GetTitleInfo('United States', 'en');

        $this->assertIsArray($result);
        $this->assertArrayHasKey('pageid', $result);
        $this->assertGreaterThan(0, $result['pageid']);
    }

    /**
     * @group readonly
     * Title containing Unicode characters (Japanese).
     */
    public function testGetTitleInfoHandlesUnicodeTitles(): void
    {
        $result = \Publish\WikiApi\GetTitleInfo('メインページ', 'ja');

        $this->assertIsArray($result);
        $this->assertArrayHasKey('pageid', $result);
    }

    /**
     * @group readonly
     * Non-existent language → cURL exception → should return null, not crash.
     */
    public function testGetTitleInfoReturnsNullForInvalidLang(): void
    {
        // zz.wikipedia.org does not exist → exception → null
        $result = \Publish\WikiApi\GetTitleInfo('SomePage', 'zz_invalid_lang_xyz');

        $this->assertNull($result);
    }
}

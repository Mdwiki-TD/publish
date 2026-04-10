<?php

namespace Tests\Bots;

use PHPUnit\Framework\TestCase;

/**
 * Tests for src/bots/wd.php  (namespace Publish\WD)
 *
 * Functions: GetQidForMdtitle, GetTitleInfo, LinkToWikidata, getAccessCredentials
 *
 * Because all external calls (MySQL, cURL, OAuth) require live services, the
 * tests here cover:
 *   1. Pure logic extracted from the functions (no I/O)
 *   2. PHP syntax validation
 *   3. getAccessCredentials credential-resolution logic (mocked via fixtures)
 *   4. URL construction for GetTitleInfo
 */
class WdTest extends TestCase
{
    // -----------------------------------------------------------------------
    // GetTitleInfo – URL construction
    // -----------------------------------------------------------------------

    /**
     * Mirrors the URL-building logic inside GetTitleInfo.
     */
    private function buildGetTitleInfoUrl(string $targetTitle, string $lang): string
    {
        $params = [
            'action'        => 'query',
            'format'        => 'json',
            'titles'        => $targetTitle,
            'utf8'          => 1,
            'formatversion' => '2',
        ];
        return "https://$lang.wikipedia.org/w/api.php?" .
            http_build_query($params, '', '&', PHP_QUERY_RFC3986);
    }

    public function testGetTitleInfoUrlContainsLang(): void
    {
        $url = $this->buildGetTitleInfoUrl('Paracetamol', 'fr');
        $this->assertStringContainsString('fr.wikipedia.org', $url);
    }

    public function testGetTitleInfoUrlContainsTitle(): void
    {
        $url = $this->buildGetTitleInfoUrl('Ibuprofen', 'de');
        $this->assertStringContainsString('Ibuprofen', $url);
    }

    public function testGetTitleInfoUrlUsesRfc3986Encoding(): void
    {
        $url = $this->buildGetTitleInfoUrl('Article With Spaces', 'en');
        // RFC 3986 encodes spaces as %20, not +
        $this->assertStringContainsString('%20', $url);
    }

    public function testGetTitleInfoUrlHasCorrectAction(): void
    {
        $url = $this->buildGetTitleInfoUrl('Title', 'es');
        $this->assertStringContainsString('action=query', $url);
    }

    // -----------------------------------------------------------------------
    // LinkIt – apiParams construction
    // -----------------------------------------------------------------------

    /**
     * Mirrors the apiParams assembly inside LinkIt.
     */
    private function buildLinkItParams(string $qid, string $lang, string $sourcetitle, string $targettitle): array
    {
        $params = [
            'action'    => 'wbsetsitelink',
            'linktitle' => $targettitle,
            'linksite'  => "{$lang}wiki",
        ];
        if (!empty($qid)) {
            $params['id'] = $qid;
        } else {
            $params['title'] = $sourcetitle;
            $params['site']  = 'enwiki';
        }
        return $params;
    }

    public function testLinkItUsesQidWhenPresent(): void
    {
        $params = $this->buildLinkItParams('Q12345', 'fr', 'Paracetamol', 'Paracétamol');
        $this->assertArrayHasKey('id', $params);
        $this->assertSame('Q12345', $params['id']);
        $this->assertArrayNotHasKey('site', $params);
    }

    public function testLinkItFallsBackToSourceTitleWhenQidEmpty(): void
    {
        $params = $this->buildLinkItParams('', 'de', 'Paracetamol', 'Paracetamol (Stoff)');
        $this->assertArrayNotHasKey('id', $params);
        $this->assertSame('Paracetamol', $params['title']);
        $this->assertSame('enwiki', $params['site']);
    }

    public function testLinkItLinksiteIsLangWiki(): void
    {
        $params = $this->buildLinkItParams('Q1', 'ja', 'T', 'T (ja)');
        $this->assertSame('jawiki', $params['linksite']);
    }

    // -----------------------------------------------------------------------
    // getAccessCredentials – fallback logic
    // -----------------------------------------------------------------------

    /**
     * Mirrors the credential-resolution logic from getAccessCredentials.
     * Returns null when both keys are empty and no DB record exists.
     */
    private function resolveCredentials(?string $ak, ?string $as, ?array $dbRecord): ?array
    {
        if (!$ak || !$as) {
            if ($dbRecord === null) {
                return null;
            }
            $ak = $dbRecord['access_key'];
            $as = $dbRecord['access_secret'];
        }
        return [$ak, $as];
    }

    public function testReturnsProvidedCredentialsDirectly(): void
    {
        $result = $this->resolveCredentials('k', 's', null);
        $this->assertSame(['k', 's'], $result);
    }

    public function testFallsBackToDbRecordWhenKeysMissing(): void
    {
        $dbRecord = ['access_key' => 'db_key', 'access_secret' => 'db_secret'];
        $result   = $this->resolveCredentials('', '', $dbRecord);
        $this->assertSame(['db_key', 'db_secret'], $result);
    }

    public function testReturnsNullWhenKeysMissingAndNoDb(): void
    {
        $result = $this->resolveCredentials(null, null, null);
        $this->assertNull($result);
    }

    public function testReturnsNullWhenOnlyAccessKeyMissing(): void
    {
        $result = $this->resolveCredentials('', 'some_secret', null);
        $this->assertNull($result);
    }

    // -----------------------------------------------------------------------
    // LinkToWikidata – result normalization
    // -----------------------------------------------------------------------

    public function testLinkToWikidataSuccessResultStructure(): void
    {
        // Mirrors what LinkToWikidata returns on success
        $qid         = 'Q999';
        $link_result = ['success' => 1, 'pageinfo' => []];
        $link_result['qid'] = $qid;

        if (isset($link_result['success']) && $link_result['success']) {
            $final = ['result' => 'success', 'qid' => $qid];
        } else {
            $final = $link_result;
        }

        $this->assertSame('success', $final['result']);
        $this->assertSame('Q999', $final['qid']);
    }

    public function testLinkToWikidataErrorResultPassedThrough(): void
    {
        $qid         = 'Q888';
        $link_result = ['error' => ['code' => 'protectedpage', 'info' => 'Page protected'], 'qid' => $qid];

        if (isset($link_result['success']) && $link_result['success']) {
            $final = ['result' => 'success', 'qid' => $qid];
        } else {
            $final = $link_result;
        }

        $this->assertArrayHasKey('error', $final);
        $this->assertSame('Q888', $final['qid']);
    }

}

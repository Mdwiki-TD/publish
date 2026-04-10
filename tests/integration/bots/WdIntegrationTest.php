<?php

namespace Tests\Bots\Integration;

use PHPUnit\Framework\TestCase;

/**
 * Integration Tests for src/bots/wd.php (namespace Publish\WD)
 *
 */
class WdIntegrationTest extends TestCase
{
    // -----------------------------------------------------------------------
    // Helpers
    // -----------------------------------------------------------------------

    /**
     * Skips the test if the write environment variables are not present.
     */
    private function requireWriteCredentials(): array
    {
        $user   = getenv('WD_TEST_USER');
        $key    = getenv('WD_TEST_ACCESS_KEY');
        $secret = getenv('WD_TEST_ACCESS_SECRET');

        if (!$user || !$key || !$secret) {
            $this->markTestSkipped(
                'env WD_TEST_USER / WD_TEST_ACCESS_KEY / WD_TEST_ACCESS_SECRET does not exist.'
            );
        }

        return [$user, $key, $secret];
    }

    // -----------------------------------------------------------------------
    // getAccessCredentials – Reading from a real DB
    // -----------------------------------------------------------------------

    /**
     * @group readonly
     * Keys passed directly should be returned as is without a DB call.
     */
    public function testGetAccessCredentialsReturnsDirectKeysWithoutDbCall(): void
    {
        $result = \Publish\WD\getAccessCredentials('anyuser', 'direct_key', 'direct_secret');

        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        $this->assertSame('direct_key', $result[0]);
        $this->assertSame('direct_secret', $result[1]);
    }

    /**
     * @group readonly
     * A user that does not exist in the DB should return null.
     */
    public function testGetAccessCredentialsReturnsNullForNonExistentUser(): void
    {
        $result = \Publish\WD\getAccessCredentials('__user_that_does_not_exist__', '', '');

        $this->assertNull($result);
    }

    /**
     * @group readonly
     * A user existing in the DB returns an array of two elements.
     * Requires WD_TEST_USER to be defined in the environment.
     */
    public function testGetAccessCredentialsReturnsArrayForKnownUser(): void
    {
        $user = getenv('WD_TEST_USER');
        if (!$user) {
            $this->markTestSkipped('WD_TEST_USER is not defined.');
        }

        $result = \Publish\WD\getAccessCredentials($user, '', '');

        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        $this->assertNotEmpty($result[0]); // access_key
        $this->assertNotEmpty($result[1]); // access_secret
    }

    // -----------------------------------------------------------------------
    // LinkToWikidata – Calling the real Wikidata API (requires credentials)
    // -----------------------------------------------------------------------

    /**
     * @group write
     * Linking an existing page to Wikidata via QID.
     * Uses a sandbox/test page to avoid modifying real data.
     */
    public function testLinkToWikidataReturnsSuccessForValidLink(): void
    {
        [$user, $key, $secret] = $this->requireWriteCredentials();

        $sandboxTitle = getenv('WD_TEST_SOURCE_TITLE') ?: '';
        $sandboxLang  = getenv('WD_TEST_TARGET_LANG')  ?: '';
        $sandboxTarget = getenv('WD_TEST_TARGET_TITLE') ?: '';

        if (!$sandboxTitle || !$sandboxLang || !$sandboxTarget) {
            $this->markTestSkipped(
                'Requires WD_TEST_SOURCE_TITLE, WD_TEST_TARGET_LANG, and WD_TEST_TARGET_TITLE.'
            );
        }

        $result = \Publish\WD\LinkToWikidata(
            $sandboxTitle,
            $sandboxLang,
            $user,
            $sandboxTarget,
            $key,
            $secret
        );

        $this->assertIsArray($result);
        // A success result contains 'result' => 'success'
        // or an acceptable error like 'no-op' if the link already exists
        $isSuccess = isset($result['result']) && $result['result'] === 'success';
        $isNoop    = isset($result['error']['code']) && $result['error']['code'] === 'modification-failed';
        $this->assertTrue($isSuccess || $isNoop, 'Result: ' . json_encode($result));
    }

    /**
     * @group write
     * A user without credentials should return an error array instead of crashing.
     */
    public function testLinkToWikidataReturnsErrorArrayForMissingCredentials(): void
    {
        $result = \Publish\WD\LinkToWikidata(
            'SomeTitle',
            'fr',
            '__nonexistent_user__',
            'Titre',
            '',  // empty access_key
            ''   // empty access_secret
        );

        $this->assertIsArray($result);
        $this->assertArrayHasKey('error', $result);
        $this->assertStringContainsString('__nonexistent_user__', $result['error']);
    }

    /**
     * @group write
     * The result always contains a QID even on error.
     */
    public function testLinkToWikidataAlwaysIncludesQidInResult(): void
    {
        // No credentials → early error, but qid should still be present
        $result = \Publish\WD\LinkToWikidata(
            'AnyTitle',
            'de',
            '__nonexistent_user__',
            'IrgendeineTitle',
            '',
            ''
        );

        $this->assertArrayHasKey('qid', $result);
        // qid is either a string (Q...) or an empty string if not found in DB
        $this->assertIsString($result['qid']);
    }
}

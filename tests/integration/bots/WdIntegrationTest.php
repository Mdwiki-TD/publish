<?php

namespace Tests\Bots\Integration;

use PHPUnit\Framework\TestCase;

/**
 * Integration Tests for src/bots/wd.php (namespace Publish\WD)
 *
 */
class WdIntegrationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Defuse keys must be instances of \Defuse\Crypto\Key or
        // variables that the library expects.
        // For this example, we assume your app usually initializes these from a string.

        $GLOBALS['decrypt_key'] = $this->getTestKey();
    }

    private function getTestKey()
    {
        // Return a dummy Key object or the string your app expects
        // This depends on how your app normally populates these globals.
        return \Defuse\Crypto\Key::createNewRandomKey();
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
        $user = "Mr. Ibrahem";

        $result = \Publish\WD\getAccessCredentials($user, '', '');

        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        // $this->assertNotEmpty($result[0]); // access_key
        // $this->assertNotEmpty($result[1]); // access_secret
    }

    // -----------------------------------------------------------------------
    // LinkToWikidata – Calling the real Wikidata API (requires credentials)
    // -----------------------------------------------------------------------

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

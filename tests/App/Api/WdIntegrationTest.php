<?php

namespace Tests\Bots\Integration;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Group;
use Publish\Api\WdApi;

use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(WdApi::class)]
class WdIntegrationTest extends TestCase
{
    private WdApi $api;

    protected function setUp(): void
    {
        parent::setUp();
        $this->api = new WdApi();

        // Defuse keys must be instances of \Defuse\Crypto\Key or
        // variables that the library expects.
        // For this example, we assume your app usually initializes these from a string.

    }

    /**
     * Keys passed directly should be returned as is without a DB call.
     */
    #[Group('network')]
    public function testGetAccessCredentialsReturnsDirectKeysWithoutDbCall(): void
    {
        $result = $this->api->getAccessCredentials('anyuser', 'direct_key', 'direct_secret');

        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        $this->assertSame('direct_key', $result[0]);
        $this->assertSame('direct_secret', $result[1]);
    }

    /**
     * A user that does not exist in the DB should return null.
     */
    #[Group('network')]
    public function testGetAccessCredentialsReturnsNullForNonExistentUser(): void
    {
        $result = $this->api->getAccessCredentials('__user_that_does_not_exist__', '', '');

        $this->assertNull($result);
    }

    // -----------------------------------------------------------------------
    // LinkToWikidata – Calling the real Wikidata API (requires credentials)
    // -----------------------------------------------------------------------

    /**
     * A user without credentials should return an error array instead of crashing.
     */
    #[Group('network')]
    public function testLinkToWikidataReturnsErrorArrayForMissingCredentials(): void
    {
        $access = [
            'access_key'    => "", // empty access_key
            'access_secret' => "",  // empty access_secret
        ];
        $result = $this->api->LinkToWikidata(
            'SomeTitle',
            'fr',
            '__nonexistent_user__',
            'Titre',
            $access,
        );

        // @phpstan-ignore method.alreadyNarrowedType
        $this->assertIsArray($result);
        $this->assertArrayHasKey('error', $result);
        $this->assertStringContainsString('__nonexistent_user__', $result['error']);
    }

    /**
     * The result always contains a QID even on error.
     */
    #[Group('network')]
    public function testLinkToWikidataAlwaysIncludesQidInResult(): void
    {
        $access = [
            'access_key'    => "", // empty access_key
            'access_secret' => "",  // empty access_secret
        ];
        // No credentials → early error, but qid should still be present
        $result = $this->api->LinkToWikidata(
            'AnyTitle',
            'de',
            '__nonexistent_user__',
            'IrgendeineTitle',
            $access
        );

        $this->assertArrayHasKey('qid', $result);
        // qid is either a string (Q...) or an empty string if not found in DB
        $this->assertIsString($result['qid']);
    }
}

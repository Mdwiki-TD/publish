<?php

namespace Tests\Bots\Integration;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Group;

use function Publish\MediaWikiClient\publish_do_edit;
use MediaWiki\OAuthClient\Token;

/**
 * Integration Tests for src/app/MediaWikiClient/MediaWikiEditClient.php
 * publish_do_edit
 */
class MediaWikiEditClientTest extends TestCase
{
    // -----------------------------------------------------------------------
    // publish_do_edit
    // -----------------------------------------------------------------------
    /**
     * Edit the Main Page in English Wikipedia.
     */
    #[Group('readonly')]
    public function testPublishDoEditEditsMainPage(): void
    {
        $access = [
            'access_key' => "access_key",
            'access_secret' => "access_secret",
        ];
        $apiParams = [
            'action' => 'edit',
            'title' => 'User:Sandbox/test Page',
            // 'section' => 'new',
            'summary' => "",
            'token' => "fake_token",
            'text' => "",
            'format' => 'json',
        ];
        $accessToken = new Token($access["access_key"], $access["access_secret"]);
        $result = publish_do_edit($apiParams, 'en', $accessToken);

        $expected_error = [
            'code' => 'mwoauth-invalid-authorization',
            'info' => 'The authorization headers in your request are not valid: Invalid consumer',
            '*' => 'See https://en.wikipedia.org/w/api.php for API usage. Subscribe to the mediawiki-api-announce mailing list at &lt;https://lists.wikimedia.org/postorius/lists/mediawiki-api-announce.lists.wikimedia.org/&gt; for notice of API deprecations and breaking changes.',
        ];

        $this->assertIsArray($result);
        $this->assertSame($expected_error, $result["error"]);

        // $this->assertArrayHasKey('edit', $result);
        // $this->assertArrayHasKey('result', $result['edit']);
        // $this->assertSame('Success', $result['edit']['result']);
    }
}

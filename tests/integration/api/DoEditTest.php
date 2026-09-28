<?php

namespace Tests\Bots\Integration;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Group;

use function Publish\DoEdit\publish_do_edit;
use MediaWiki\OAuthClient\Token;

/**
 * Integration Tests for src/app/api/DoEdit.php
 * publish_do_edit
 */
class DoEditTest extends TestCase
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

        $accessToken = new Token($access["access_key"], $access["access_secret"]);
        $result = publish_do_edit('Main Page', 'en', $accessToken);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('edit', $result);
        $this->assertArrayHasKey('result', $result['edit']);
        $this->assertSame('Success', $result['edit']['result']);
    }
}

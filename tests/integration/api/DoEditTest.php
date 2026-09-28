<?php

namespace Tests\Bots\Integration;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Group;

use function Publish\DoEdit\publish_do_edit;

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
        $result = publish_do_edit('Main Page', 'en', 'Test edit', 'Test edit summary', 'Doc James');

        $this->assertIsArray($result);
        $this->assertArrayHasKey('edit', $result);
        $this->assertArrayHasKey('result', $result['edit']);
        $this->assertSame('Success', $result['edit']['result']);
    }

}

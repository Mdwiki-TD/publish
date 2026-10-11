<?php

/**
 * Seed test for: Settings
 * Source: src/app/Settings.php
 * TODO: write the actual tests.
 */

declare(strict_types=1);

namespace Tests\Publish;

use Publish\Settings;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(Settings::class)]
class SettingsTest extends TestCase
{
    public function testPlaceholder(): void
    {
        $this->markTestIncomplete('Tests for Settings are not written yet.');
    }
}

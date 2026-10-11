<?php

/**
 * Seed test for: TextChanger
 * Source: src/app/Process/TextChanger.php
 * TODO: write the actual tests.
 */

declare(strict_types=1);

namespace Tests\Publish\Process;

use Publish\Process\TextChanger;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(TextChanger::class)]
class TextChangerTest extends TestCase
{
    public function testPlaceholder(): void
    {
        $this->markTestIncomplete('Tests for TextChanger are not written yet.');
    }
}

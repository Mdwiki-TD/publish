<?php

/**
 * Seed test for: Logger
 * Source: src/app/Logger.php
 * TODO: write the actual tests.
 */

declare(strict_types=1);

namespace Tests\Publish;
use Publish\Logger;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(Logger::class)]
class LoggerTest extends TestCase
{
    public function testPlaceholder(): void
    {
        $this->markTestIncomplete('Tests for Logger are not written yet.');
    }
}

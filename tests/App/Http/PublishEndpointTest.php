<?php

/**
 * Seed test for: PublishEndpoint
 * Source: src/app/Http/PublishEndpoint.php
 * TODO: write the actual tests.
 */

declare(strict_types=1);

namespace Tests\Publish\Http;

use Publish\Http\PublishEndpoint;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(PublishEndpoint::class)]
class PublishEndpointTest extends TestCase
{
    public function testPlaceholder(): void
    {
        $this->markTestIncomplete('Tests for PublishEndpoint are not written yet.');
    }
}

<?php

/**
 * Seed test for: MediaWikiOAuthClientFactory
 * Source: src/app/MediaWikiClient/MediaWikiOAuthClientFactory.php
 * TODO: write the actual tests.
 */

declare(strict_types=1);

namespace Tests\Publish\MediaWikiClient;

use Publish\MediaWikiClient\MediaWikiOAuthClientFactory;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(MediaWikiOAuthClientFactory::class)]
class MediaWikiOAuthClientFactoryTest extends TestCase
{
    public function testPlaceholder(): void
    {
        $this->markTestIncomplete('Tests for MediaWikiOAuthClientFactory are not written yet.');
    }
}

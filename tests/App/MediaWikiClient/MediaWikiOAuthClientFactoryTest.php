<?php

declare(strict_types=1);

namespace Tests\Publish\MediaWikiClient;

use PHPUnit\Framework\TestCase;
use Publish\MediaWikiClient\MediaWikiOAuthClientFactory;
use Publish\MediaWikiClient\MediaWikiOAuthClientAdapter;

use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(MediaWikiOAuthClientFactory::class)]
final class MediaWikiOAuthClientFactoryTest extends TestCase
{
    public function testCreateForDomainReturnsAdapter(): void
    {
        $factory = new MediaWikiOAuthClientFactory('consumer_key', 'consumer_secret', 'UserAgent/1.0');
        $adapter = $factory->createForDomain('https://en.wikipedia.org');

        $this->assertInstanceOf(MediaWikiOAuthClientAdapter::class, $adapter);
    }
}

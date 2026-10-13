<?php

declare(strict_types=1);

namespace Tests\MediaWikiClient;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Publish\MediaWikiClient\MediaWikiOAuthClientAdapter;
use Publish\MediaWikiClient\MediaWikiOAuthClientFactory;

#[CoversClass(MediaWikiOAuthClientFactory::class)]
final class MediaWikiOAuthClientFactoryTest extends TestCase
{
    public function testCreateForDomainReturnsAdapterWithCustomCredentials(): void
    {
        $factory = new MediaWikiOAuthClientFactory('custom_key', 'custom_secret', 'CustomUserAgent/1.0');
        $adapter = $factory->createForDomain('https://en.wikipedia.org');

        $this->assertInstanceOf(MediaWikiOAuthClientAdapter::class, $adapter);
    }

    public function testCreateForDomainFallbackToEnvVars(): void
    {
        putenv('CONSUMER_KEY=env_consumer_key');
        putenv('CONSUMER_SECRET=env_consumer_secret');

        $factory = new MediaWikiOAuthClientFactory();
        $adapter = $factory->createForDomain('https://ar.wikipedia.org');

        $this->assertInstanceOf(MediaWikiOAuthClientAdapter::class, $adapter);
    }
}

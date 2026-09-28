<?php

namespace Publish\MediaWikiClient;

use Publish\MediaWikiClient\OAuthHttpClientInterface;

/**
 * Creates an OAuthHttpClientInterface for a given wiki domain.
 *
 * A factory is needed (instead of injecting a single client) because the
 * original code builds a new Client per domain/call, using the domain to
 * construct the OAuth URL.
 */
interface OAuthClientFactoryInterface
{
    public function createForDomain(string $httpsDomain): OAuthHttpClientInterface;
}

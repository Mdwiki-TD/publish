<?php

namespace Publish\MediaWikiClient;

use Publish\MediaWikiClient\OAuthClientFactoryInterface;
use Publish\MediaWikiClient\MediaWikiOAuthClientAdapter;

use MediaWiki\OAuthClient\Client;
use MediaWiki\OAuthClient\ClientConfig;
use MediaWiki\OAuthClient\Consumer;

class MediaWikiOAuthClientFactory implements OAuthClientFactoryInterface
{
    private const DEFAULT_USER_AGENT = 'mdwiki MediaWiki OAuth Client/1.0';

    private string $consumerKey;
    private string $consumerSecret;
    private string $userAgent;

    /**
     * Consumer key/secret default to the CONSUMER_KEY / CONSUMER_SECRET
     * environment variables when not passed explicitly. Reading getenv()
     * here (instead of deep inside a free function) means tests can bypass
     * it entirely by passing values directly to the constructor.
     */
    public function __construct(
        ?string $consumerKey = null,
        ?string $consumerSecret = null,
        string $userAgent = self::DEFAULT_USER_AGENT
    ) {
        $this->consumerKey = $consumerKey ?? (getenv('CONSUMER_KEY') ?: '');
        $this->consumerSecret = $consumerSecret ?? (getenv('CONSUMER_SECRET') ?: '');
        $this->userAgent = $userAgent;
    }

    public function createForDomain(string $httpsDomain): MediaWikiOAuthClientAdapter
    {
        $domain = parse_url($httpsDomain, PHP_URL_HOST);
        $oauthUrl = "https://$domain/w/index.php?title=Special:OAuth";

        // Configure the OAuth client with the URL and consumer details.
        $conf = new ClientConfig($oauthUrl);
        $conf->setConsumer(new Consumer($this->consumerKey, $this->consumerSecret));

        $conf->setUserAgent($this->userAgent);

        $client = new Client($conf);
        return new MediaWikiOAuthClientAdapter($client);
    }
}

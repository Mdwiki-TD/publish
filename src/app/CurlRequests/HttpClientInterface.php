<?php

namespace Publish\CurlRequests;

/**
 * Abstraction over "fetch a URL and get its body back".
 *
 * Having this interface is what makes network-dependent code testable:
 * tests can inject a fake/mock implementation instead of CurlHttpClient,
 * so they never touch the real network.
 */
interface HttpClientInterface
{
    /**
     * @return string|null The response body, or null if the request failed
     *                      (connection error, timeout, DNS failure, etc.)
     */
    public function get(string $url): ?string;
}

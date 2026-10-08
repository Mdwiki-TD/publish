<?php
namespace Publish\CurlRequests;

use Publish\CurlRequests\HttpClientInterface;

class CurlHttpClient implements HttpClientInterface
{
    private const DEFAULT_USER_AGENT = 'WikiProjectMed Translation Dashboard/1.0 (https://mdwiki.toolforge.org/; tools.mdwiki@toolforge.org)';

    private string $userAgent;
    private int $connectTimeout;
    private int $timeout;

    public function __construct(
        string $userAgent = self::DEFAULT_USER_AGENT,
        int $connectTimeout = 5,
        int $timeout = 5
    ) {
        $this->userAgent      = $userAgent;
        $this->connectTimeout = $connectTimeout;
        $this->timeout        = $timeout;
    }

    public function get(string $url): ?string
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        // curl_setopt($ch, CURLOPT_COOKIEJAR, "cookie.txt");
        // curl_setopt($ch, CURLOPT_COOKIEFILE, "cookie.txt");
        curl_setopt($ch, CURLOPT_USERAGENT, $this->userAgent);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $this->connectTimeout);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);

        $output = curl_exec($ch);

        if ($output === false) {
            error_log('<br>cURL Error: ' . curl_error($ch) . "<br>$url");
            curl_close($ch);

            return null;
        }

        curl_close($ch);

        return $output;
    }
}

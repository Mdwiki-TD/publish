<?PHP
namespace Publish;

use Publish\Logger;

const ALLOWED_DOMAINS = [
    'mdwikicx.toolforge.org',
    'mdwiki.toolforge.org',
    'medwiki.toolforge.org',
];

class Cors
{
    public static function isAllowed(?array $serverRequest = null): bool | string
    {
        $serverRequest = $serverRequest ?? $_SERVER;
        $env           = getenv('APP_ENV') ?: ($_ENV['APP_ENV'] ?? '');

        if ($env === 'development') {
            Logger::debug("Skip Cors checking in development env");
            return true;
        }

        // Check if the request is coming from allowed domains
        $referer = isset($serverRequest['HTTP_REFERER']) ? $serverRequest['HTTP_REFERER'] : '';
        $origin  = isset($serverRequest['HTTP_ORIGIN']) ? $serverRequest['HTTP_ORIGIN'] : '';

        $allowed = false;
        foreach (ALLOWED_DOMAINS as $domain) {
            // if (strpos($referer, $domain) !== false || strpos($origin, $domain) !== false) {
            if (parse_url($referer, PHP_URL_HOST) === $domain || parse_url($origin, PHP_URL_HOST) === $domain) {
                $allowed = $domain;
                break;
            }
        }
        // log $serverRequest to file
        // file_put_contents(__DIR__ . '/cors.log', print_r($serverRequest, true));

        return $allowed;
    }
}

<?PHP

namespace Publish\CORS;

function is_allowed()
{

    $env = getenv('APP_ENV') ?: ($_ENV['APP_ENV'] ?? '');

    if ($env === 'development') {
        return true;
    }

    $domains = ['medwiki.toolforge.org', 'mdwikicx.toolforge.org'];
    // Check if the request is coming from allowed domains
    $referer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';
    $origin = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '';

    $isAllowed = false;
    foreach ($domains as $domain) {
        if (strpos($referer, $domain) !== false || strpos($origin, $domain) !== false) {
            $isAllowed = $domain;
            break;
        }
    }

    // log $_SERVER to file
    // file_put_contents(__DIR__ . '/cors.log', print_r($_SERVER, true));

    return $isAllowed;
}

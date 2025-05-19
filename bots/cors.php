<?PHP

namespace Publish\CORS;

/*

use function Publish\CORS\is_allowed;

*/

$domains = ['medwiki.toolforge.org', 'mdwikicx.toolforge.org'];

function is_allowed()
{
    global $domains;
    // Check if the request is coming from allowed domains
    $referer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';
    $origin = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '';

    $is_allowed = false;
    foreach ($domains as $domain) {
        if (strpos($referer, $domain) !== false || strpos($origin, $domain) !== false) {
            $is_allowed = $domain;
            break;
        }
    }

    // log $_SERVER to file
    // file_put_contents(__DIR__ . '/cors.log', print_r($_SERVER, true));

    return $is_allowed;
}

<?PHP

namespace Publish\CORS;

/*

use function Publish\CORS\is_allowed;
use function Publish\CORS\allowed_domains;

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
            $is_allowed = true;
            break;
        }
    }
    // return $is_allowed;
    return true;
}

function allowed_domains()
{
    global $domains;
    return $domains;
}

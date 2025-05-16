<?PHP
header('Content-Type: application/json; charset=utf-8');

// Check if the request is coming from allowed domains
$allowed_domains = ['medwiki.toolforge.org', 'mdwikicx.toolforge.org'];
$referer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';
$origin = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '';

$is_allowed = false;
foreach ($allowed_domains as $domain) {
    if (strpos($referer, $domain) !== false || strpos($origin, $domain) !== false) {
        $is_allowed = true;
        break;
    }
}

if (!$is_allowed) {
    http_response_code(403); // Forbidden
    echo json_encode(['error' => 'Access denied. Requests are only allowed from authorized domains.']);
    exit;
}

header("Access-Control-Allow-Origin: " . implode(", ", $allowed_domains));

include_once __DIR__ . '/include.php';

include_once __DIR__ . '/start.php';

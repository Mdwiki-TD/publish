<?PHP
header('Content-Type: application/json; charset=utf-8');

include_once __DIR__ . '/cors.php';

use function Publish\CORS\allowed_domains;
use function Publish\CORS\is_allowed;

if (!is_allowed()) {
    http_response_code(403); // Forbidden
    echo json_encode(['error' => 'Access denied. Requests are only allowed from authorized domains.']);

    if (isset($_GET['var_export'])) var_export($_SERVER);

    exit;
}

// header("Access-Control-Allow-Origin: " . implode(", ", allowed_domains()));

include_once __DIR__ . '/include.php';

include_once __DIR__ . '/start.php';

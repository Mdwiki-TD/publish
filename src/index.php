<?PHP
header('Content-Type: application/json; charset=utf-8');

// Check if the request is a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // Method Not Allowed
    echo json_encode(['error' => 'Only POST requests are allowed']);
    exit;
}

/*
include_once __DIR__ . '/su/cors.php';

use function Publish\CORS\is_allowed;

$alowed = is_allowed();

if (!$alowed) {
    http_response_code(403); // Forbidden
    echo json_encode(['error' => 'Access denied. Requests are only allowed from authorized domains.']);
    exit;
}
header("Access-Control-Allow-Origin: https://$alowed");
*/

include_once __DIR__ . '/su/include.php';
include_once __DIR__ . '/su/start.php';

start($_POST);

<?PHP
header('Content-Type: application/json; charset=utf-8');

include_once __DIR__ . '/cors.php';

use function Publish\CORS\is_allowed;

// $alowed = is_allowed();

// if (!$alowed) {
//     http_response_code(403); // Forbidden
//     echo json_encode(['error' => 'Access denied. Requests are only allowed from authorized domains.']);
//     exit;
// }

// header("Access-Control-Allow-Origin: https://$alowed");

include_once __DIR__ . '/include.php';

include_once __DIR__ . '/start.php';

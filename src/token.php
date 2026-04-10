<?PHP
header('Content-Type: application/json; charset=utf-8');

include_once __DIR__ . '/su/include.php';

use function Publish\CORS\is_allowed;
use function Publish\TokenHandler\handle_token;

$alowed = is_allowed();

if (!$alowed) {
    http_response_code(403); // Forbidden
    echo json_encode(['error' => 'Access denied. Requests are only allowed from authorized domains.']);
    exit;
}

header("Access-Control-Allow-Origin: https://$alowed");

$wiki    = $_GET['wiki'] ?? '';
$user    = $_GET['user'] ?? '';

if (empty($wiki) || empty($user)) {
    print(json_encode(['error' => ['code' => 'no data', 'info' => 'wiki or user is empty']], JSON_PRETTY_PRINT));
    exit(1);
}

handle_token($wiki, $user);

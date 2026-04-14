<?PHP
header('Content-Type: application/json; charset=utf-8');

// Check if the request is a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // Method Not Allowed
    echo json_encode(['error' => 'Only POST requests are allowed']);
    exit;
}

include_once __DIR__ . '/su/include.php';

use function Publish\Start\start;

/*

use function Publish\CORS\is_allowed;

$alowed = is_allowed();

if (!$alowed) {
    http_response_code(403); // Forbidden
    echo json_encode(['error' => 'Access denied. Requests are only allowed from authorized domains.']);
    exit;
}
header("Access-Control-Allow-Origin: https://$alowed");
*/


function check_publish_secret_code()
{
    // load publish_secret_code from headers['X-Secret-Key']
    $publish_secret_code = getenv('PUBLISH_SECRET_CODE');
    if ($publish_secret_code === false) {
        $publish_secret_code = $_ENV['PUBLISH_SECRET_CODE'] ?? '';
    }

    if ($publish_secret_code === '') {
        return true;
    }
    $received_key = $_SERVER['HTTP_X_SECRET_KEY'] ?? '';

    // if ($received_key === $publish_secret_code) {
    if (hash_equals($publish_secret_code, $received_key)) {
        return true;
    }

    return false;
}

if (!check_publish_secret_code()) {
    http_response_code(403); // Forbidden
    echo json_encode(['error' => 'Access denied. Invalid or missing secret key.']);
    exit;
}

start($_POST);

<?PHP
header('Content-Type: application/json; charset=utf-8');

include_once __DIR__ . '/bots/cors.php';

use function Publish\CORS\is_allowed;

$alowed = is_allowed();

if (!$alowed) {
    http_response_code(403); // Forbidden
    echo json_encode(['error' => 'Access denied. Requests are only allowed from authorized domains.']);
    exit;
}

header("Access-Control-Allow-Origin: https://$alowed");

include_once __DIR__ . '/../auth/vendor_load.php';

include_once __DIR__ . '/bots/config.php';
include_once __DIR__ . '/bots/helps.php';
include_once __DIR__ . '/bots/access_helps.php';
include_once __DIR__ . '/bots/access_helps_new.php';
include_once __DIR__ . '/bots/get_token.php';

use function Publish\GetToken\get_cxtoken;
use function Publish\AccessHelps\get_access_from_db;
use function Publish\AccessHelpsNew\get_access_from_db_new;
use function Publish\AccessHelps\del_access_from_db;
use function Publish\AccessHelpsNew\del_access_from_db_new;

$wiki    = $_GET['wiki'] ?? '';
$user    = $_GET['user'] ?? '';
$ty      = $_GET['ty'] ?? '';

$specialUsers = [
    "Mr. Ibrahem 1" => "Mr. Ibrahem",
    "Admin" => "Mr. Ibrahem"
];
$user = $specialUsers[$user] ?? $user;

if (empty($wiki) || empty($user)) {
    print(json_encode(['error' => ['code' => 'no data', 'info' => 'wiki or user is empty']], JSON_PRETTY_PRINT));
    exit(1);
}

$access = get_access_from_db_new($user);
// ---
if ($access === null) {
    $access = get_access_from_db($user);
}
// ---
if ($access == null) {
    $cxtoken = ['error' => ['code' => 'no access', 'info' => 'no access'], 'username' => $user];
    print(json_encode($cxtoken, JSON_PRETTY_PRINT));
    // status code 403
    header('HTTP/1.0 403 Forbidden');
    exit(1);
} else {
    $access_key = $access['access_key'];
    $access_secret = $access['access_secret'];

    $cxtoken = get_cxtoken($wiki, $access_key, $access_secret) ?? ['error' => 'no cxtoken'];
}

$err = $cxtoken['csrftoken_data']["error"]["code"] ?? null;

if ($err == "mwoauth-invalid-authorization-invalid-user") {
    // ---
    del_access_from_db_new($user);
    del_access_from_db($user);
    // ---
    $cxtoken["del_access"] = true;
}

print(json_encode($cxtoken, JSON_PRETTY_PRINT));

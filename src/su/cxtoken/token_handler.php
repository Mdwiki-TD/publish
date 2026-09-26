<?PHP

namespace Publish\TokenHandler;

use function Publish\GetToken\get_cxtoken;
use function Publish\AccessHelps\get_access_from_db;
use function Publish\AccessHelps\del_access_from_db;

function handle_user_name($user)
{
    $specialUsers = [
        "Mr. Ibrahem 1" => "Mr. Ibrahem",
        "Admin" => "Mr. Ibrahem"
    ];
    $user = $specialUsers[$user] ?? $user;
    return $user;
}

function handle_token($wiki, $user)
{
    $user = handle_user_name($user);

    $access = get_access_from_db($user);

    if (empty($access)) {
        $cxtoken = ['error' => ['code' => 'noaccess', 'info' => 'noaccess'], 'username' => $user];
        http_response_code(403);
        print(json_encode($cxtoken, JSON_PRETTY_PRINT));
        header('HTTP/1.0 403 Forbidden');
        exit(1);
    }

    $accessKey = $access['access_key'];
    $accessSecret = $access['access_secret'];
    $cxtoken = get_cxtoken($wiki, $accessKey, $accessSecret) ?? ['error' => 'no cxtoken'];

    $err = $cxtoken['csrftoken_data']["error"]["code"] ?? null;

    $invalidAuthorizationErrors = [
        "mwoauth-invalid-authorization-invalid-user",
        "mwoauth-invalid-authorization"
    ];
    if (in_array($err, $invalidAuthorizationErrors)) {
        del_access_from_db($user);
        $cxtoken["del_access"] = true;
    }

    print(json_encode($cxtoken, JSON_PRETTY_PRINT));
}

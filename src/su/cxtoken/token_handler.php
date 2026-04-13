<?PHP

namespace Publish\TokenHandler;

use function Publish\GetToken\get_cxtoken;
use function Publish\AccessHelps\get_user_access;
use function Publish\AccessHelps\delete_user_access;

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

    $access = get_user_access($user);

    if (empty($access)) {
        $cxtoken = ['error' => ['code' => 'no access', 'info' => 'no access'], 'username' => $user];
        http_response_code(403);
        print(json_encode($cxtoken, JSON_PRETTY_PRINT));
        header('HTTP/1.0 403 Forbidden');
        exit(1);
    }

    $access_key = $access['access_key'];
    $access_secret = $access['access_secret'];
    $cxtoken = get_cxtoken($wiki, $access_key, $access_secret) ?? ['error' => 'no cxtoken'];

    $err = $cxtoken['csrftoken_data']["error"]["code"] ?? null;

    if ($err == "mwoauth-invalid-authorization-invalid-user") {
        delete_user_access($user);
        $cxtoken["del_access"] = true;
    }

    print(json_encode($cxtoken, JSON_PRETTY_PRINT));
}

<?PHP
// src/app/Http/CxTokenEndpoint.php

namespace Publish\Http;

use Publish\MediaWikiClient\MediaWikiEditClient;

use function Publish\AccessHelps\get_access_from_db;
use function Publish\AccessHelps\del_access_from_db;
use function Publish\CORS\is_allowed;
use function Publish\Helps\pub_test_print;

/**
 * HTTP endpoint that returns a cxtoken for a given wiki and user.
 *
 * Flow:
 *   1. CORS check (allowed domains only)
 *   2. validate `wiki` and `user` query params
 *   3. map special users, load OAuth access from DB
 *   4. request cxtoken from the wiki
 *   5. drop the stored access if the wiki reports it as invalid
 */
class CxTokenEndpoint
{
    /** Users whose requests are served with another account's credentials. */
    private const SPECIAL_USERS = [
        "Mr. Ibrahem 1" => "Mr. Ibrahem",
        "Admin"         => "Mr. Ibrahem",
    ];

    /** OAuth errors meaning the stored access is no longer valid. */
    private const INVALID_AUTHORIZATION_ERRORS = [
        "mwoauth-invalid-authorization-invalid-user",
        "mwoauth-invalid-authorization",
    ];

    private MediaWikiEditClient $client;

    public function __construct(?MediaWikiEditClient $client = null)
    {
        $this->client = $client ?? new MediaWikiEditClient();
    }

    // ------------------------------------------------------------
    // Entry point
    // ------------------------------------------------------------

    public function run(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        $allowedDomain = is_allowed();

        if (!$allowedDomain) {
            $this->fail(403, 'Access denied. Requests are only allowed from authorized domains.');
        }

        header("Access-Control-Allow-Origin: https://$allowedDomain");

        $wiki = $_GET['wiki'] ?? '';
        $user = $_GET['user'] ?? '';

        if (empty($wiki) || empty($user)) {
            print(json_encode(['error' => ['code' => 'no data', 'info' => 'wiki or user is empty']], JSON_PRETTY_PRINT));
            exit(1);
        }

        $cxtoken = handle_token($wiki, $user);

        print(json_encode($cxtoken, JSON_PRETTY_PRINT));
    }
    function get_cxtoken($wiki, $access)
    {

        $accessKey = $access['access_key'];
        $accessSecret = $access['access_secret'];

        $httpsDomain = "https://$wiki.wikipedia.org";
        $apiParams = [
            'action' => 'cxtoken',
            'format' => 'json',
        ];

        $editClient = new MediaWikiEditClient();
        $response = $editClient->postParams(
            (array) $apiParams,
            (string) $httpsDomain,
            (string) $accessKey,
            (string) $accessSecret,
        );

        $apiResult = json_decode($response, true);

        if ($apiResult == null || isset($apiResult['error'])) {
            pub_test_print("<br>get_cxtoken: Error: " . json_last_error() . " " . json_last_error_msg());
        }

        return $apiResult;
    }

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
        $cxtoken = get_cxtoken($wiki, $access) ?? ['error' => 'no cxtoken'];

        $err = $cxtoken['csrftoken_data']["error"]["code"] ?? null;

        $invalidAuthorizationErrors = [
            "mwoauth-invalid-authorization-invalid-user",
            "mwoauth-invalid-authorization"
        ];
        if (in_array($err, $invalidAuthorizationErrors)) {
            del_access_from_db($user);
            $cxtoken["del_access"] = true;
        }
        return $cxtoken;
    }

    // ------------------------------------------------------------
    // Response helpers
    // ------------------------------------------------------------

    private function respond(array $data): void
    {
        print(json_encode($data, JSON_PRETTY_PRINT));
    }

    /** @param string|array $error message or {code, info} array */
    private function fail(int $statusCode, $error): never
    {
        http_response_code($statusCode);
        $this->respond(['error' => $error]);
        exit(1);
    }
}

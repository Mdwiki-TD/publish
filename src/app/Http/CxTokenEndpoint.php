<?PHP
// src/app/Http/CxTokenEndpoint.php

namespace Publish\Http;

use function Publish\AccessHelps\del_access_from_db;
use function Publish\AccessHelps\get_access_from_db;
use function Publish\Helps\pub_test_print;
use Publish\Cors;
use Publish\MediaWikiClient\MediaWikiEditClient;

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

    /** Wiki must be a plain language code (e.g. "en", "ar", "zh-classical", "simple"); it is interpolated into the API URL. */
    private const WIKI_PATTERN = '/^[a-z]{2,12}(-[a-z0-9]+)?$/';

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

        $allowedDomain = Cors::isAllowed();

        if (! $allowedDomain) {
            $this->fail(403, 'Access denied. Requests are only allowed from authorized domains.');
        }

        header("Access-Control-Allow-Origin: https://$allowedDomain");

        $wiki = $_GET['wiki'] ?? '';
        $user = $_GET['user'] ?? '';

        if (empty($wiki) || empty($user)) {
            $this->fail(400, ['code' => 'no data', 'info' => 'wiki or user is empty']);
        }

        // $wiki is interpolated into the request URL, so it must be a plain language code;
        // anything else (e.g. "evil.com/x?a=") would send the OAuth-signed request off-wiki.
        if (! preg_match(self::WIKI_PATTERN, $wiki)) {
            $this->fail(400, ['code' => 'badwiki', 'info' => 'invalid wiki']);
        }

        $this->respond($this->handleToken($wiki, $user));
    }

    // ------------------------------------------------------------
    // Core logic
    // ------------------------------------------------------------

    public function handleToken(string $wiki, string $user): array
    {
        $user   = $this->resolveUserName($user);
        $access = get_access_from_db($user);

        if (empty($access)) {
            // set the status before any output, so the header cannot be "already sent"
            http_response_code(403);
            $this->respond([
                'error'    => ['code' => 'noaccess', 'info' => 'noaccess'],
                'username' => $user,
            ]);
            exit(1);
        }

        $cxtoken = $this->getCxToken($wiki, $access) ?? ['error' => 'no cxtoken'];

        if ($this->isInvalidAuthorization($cxtoken)) {
            del_access_from_db($user);
            $cxtoken["del_access"] = true;
        }

        return $cxtoken;
    }

    public function resolveUserName(string $user): string
    {
        return self::SPECIAL_USERS[$user] ?? $user;
    }

    public function getCxToken(string $wiki, array $access): ?array
    {
        $response = $this->client->postParams(
            ['action' => 'cxtoken', 'format' => 'json'],
            "https://$wiki.wikipedia.org",
            (string) $access['access_key'],
            (string) $access['access_secret']
        );

        $apiResult = json_decode($response, true);

        if ($apiResult === null || isset($apiResult['error'])) {
            pub_test_print("<br>getCxToken: Error: " . json_last_error() . " " . json_last_error_msg());
        }

        return $apiResult;
    }

    private function isInvalidAuthorization(array $cxtoken): bool
    {
        $code = $cxtoken['csrftoken_data']['error']['code'] ?? null;

        return in_array($code, self::INVALID_AUTHORIZATION_ERRORS, true);
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

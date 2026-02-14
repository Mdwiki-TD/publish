<?php

declare(strict_types=1);

/**
 * Helper utilities for MDWiki publishing system.
 *
 * Provides common utility functions for debugging output, encryption/decryption,
 * and HTTP requests used throughout the publishing workflow.
 *
 * @package Publish\Helps
 * @author  MDWiki Team
 * @since   1.0.0
 *
 * @example
 * use function Publish\Helps\pub_test_print;
 * use function Publish\Helps\encode_value;
 * use function Publish\Helps\decode_value;
 *
 * pub_test_print("Debug message"); // Only prints when ?test parameter present
 * $encrypted = encode_value("secret", "decrypt");
 * $decrypted = decode_value($encrypted, "decrypt");
 */

namespace Publish\Helps;

include_once __DIR__ . '/../include.php';

use Defuse\Crypto\Crypto;
use Defuse\Crypto\Key;

/**
 * User agent string for HTTP requests to Wikipedia/Wikimedia APIs.
 *
 * @var string
 */
$usr_agent = 'WikiProjectMed Translation Dashboard/1.0 (https://mdwiki.toolforge.org/; tools.mdwiki@toolforge.org)';

/**
 * Outputs debug information when test mode is enabled.
 *
 * Only produces output when the 'test' query parameter is present in the request.
 * Accepts both strings and arrays/objects (printed with print_r).
 *
 * @param string|mixed $s The value to print (string or any printable type)
 *
 * @return void
 *
 * @example
 * // Only prints if URL has ?test parameter
 * pub_test_print("Processing started");
 * pub_test_print(['key' => 'value']); // Prints array structure
 */
function pub_test_print($s): void
{
    if (!isset($_REQUEST['test'])) {
        return;
    }

    if (gettype($s) === 'string') {
        echo "\n<br>\n" . htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
    } else {
        echo "\n<br>\n";
        print_r($s);
    }
}

/**
 * Decrypts an encrypted value using the specified key type.
 *
 * Supports two key types:
 * - 'cookie': For cookie-based encryption (default)
 * - 'decrypt': For general data encryption
 *
 * SECURITY NOTE: Returns empty string on decryption failure without logging.
 * @see ANALYSIS_REPORT.md LOG-006
 *
 * @param string $value    The encrypted value to decrypt
 * @param string $key_type The key type to use ('cookie' or 'decrypt')
 *
 * @return string The decrypted value, or empty string on failure
 *
 * @throws \Defuse\Crypto\Exception\WrongKeyOrModifiedCiphertextException If key is wrong (caught internally)
 *
 * @example
 * $decrypted = decode_value($encrypted_token, 'decrypt');
 * if (empty($decrypted)) {
 *     // Handle decryption failure
 * }
 */
function decode_value(string $value, string $key_type = "cookie"): string
{
    global $cookie_key, $decrypt_key;

    if (empty(trim($value))) {
        return "";
    }

    $use_key = ($key_type === "decrypt") ? $decrypt_key : $cookie_key;

    try {
        return Crypto::decrypt($value, $use_key);
    } catch (\Exception $e) {
        // LOGGING ISSUE: Silent failure - should log for security monitoring
        // @see ANALYSIS_REPORT.md LOG-006
        return "";
    }
}

/**
 * Encrypts a value using the specified key type.
 *
 * Supports two key types:
 * - 'cookie': For cookie-based encryption (default)
 * - 'decrypt': For general data encryption
 *
 * @param string $value    The plaintext value to encrypt
 * @param string $key_type The key type to use ('cookie' or 'decrypt')
 *
 * @return string The encrypted value, or empty string on failure
 *
 * @example
 * $encrypted = encode_value($oauth_token, 'decrypt');
 * // Store $encrypted in database
 */
function encode_value(string $value, string $key_type = "cookie"): string
{
    global $cookie_key, $decrypt_key;

    $use_key = ($key_type === "decrypt") ? $decrypt_key : $cookie_key;

    if (empty(trim($value))) {
        return "";
    }

    try {
        return Crypto::encrypt($value, $use_key);
    } catch (\Exception $e) {
        return "";
    }
}

/**
 * Fetches content from a URL using cURL.
 *
 * Makes an HTTP GET request with configured timeout and user agent.
 * Used for fetching data from Wikipedia/Wikimedia APIs.
 *
 * PERFORMANCE NOTE: Synchronous call with 5-second timeout.
 * @see ANALYSIS_REPORT.md PERF-004
 *
 * @param string $url The URL to fetch
 *
 * @return string The response body, or empty string on failure
 *
 * @example
 * $json_data = get_url_curl("https://en.wikipedia.org/w/api.php?action=query&format=json");
 * $data = json_decode($json_data, true);
 */
function get_url_curl(string $url): string
{
    global $usr_agent;

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_USERAGENT, $usr_agent);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);

    $output = curl_exec($ch);

    if ($output === false) {
        pub_test_print("<br>cURL Error: " . curl_error($ch) . "<br>$url");
    }

    curl_close($ch);

    return $output ?: "";
}

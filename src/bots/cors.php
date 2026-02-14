<?php

declare(strict_types=1);

/**
 * CORS (Cross-Origin Resource Sharing) validation.
 *
 * This module provides functions to validate that requests are
 * coming from authorized domains (medwiki.toolforge.org, mdwikicx.toolforge.org).
 *
 * SECURITY WARNING: The validation uses strpos() which can be bypassed.
 * @see ANALYSIS_REPORT.md SEC-005
 *
 * SECURITY WARNING: CORS validation is currently disabled in index.php.
 * @see ANALYSIS_REPORT.md SEC-004
 *
 * @package Publish\CORS
 * @author  MDWiki Team
 * @since   1.0.0
 *
 * @example
 * use function Publish\CORS\is_allowed;
 *
 * $domain = is_allowed();
 * if (!$domain) {
 *     http_response_code(403);
 *     exit('Access denied');
 * }
 */

namespace Publish\CORS;

/**
 * List of allowed domains for CORS requests.
 *
 * @var array<int, string>
 */
$domains = ['medwiki.toolforge.org', 'mdwikicx.toolforge.org'];

/**
 * Validates that the request originates from an allowed domain.
 *
 * Checks both the Referer and Origin headers against the list
 * of allowed domains. Returns the matching domain if valid,
 * or false if not allowed.
 *
 * SECURITY WARNING: Uses strpos() which can be bypassed by subdomains
 * like "evilmdwiki.toolforge.org.evil.com". Should use parse_url()
 * and exact domain matching instead.
 * @see ANALYSIS_REPORT.md SEC-005
 *
 * @return string|false The allowed domain name, or false if not allowed
 *
 * @example
 * $allowed = is_allowed();
 * if ($allowed) {
 *     header("Access-Control-Allow-Origin: https://$allowed");
 * } else {
 *     http_response_code(403);
 *     echo json_encode(['error' => 'Access denied']);
 *     exit;
 * }
 */
function is_allowed()
{
    global $domains;

    $referer = $_SERVER['HTTP_REFERER'] ?? '';
    $origin = $_SERVER['HTTP_ORIGIN'] ?? '';

    $is_allowed = false;

    foreach ($domains as $domain) {
        // SECURITY ISSUE: strpos can match partial strings
        // @see ANALYSIS_REPORT.md SEC-005
        // Better approach:
        // $refererHost = parse_url($referer, PHP_URL_HOST);
        // $originHost = parse_url($origin, PHP_URL_HOST);
        // if ($refererHost === $domain || $originHost === $domain) { ... }
        if (strpos($referer, $domain) !== false || strpos($origin, $domain) !== false) {
            $is_allowed = $domain;
            break;
        }
    }

    return $is_allowed;
}

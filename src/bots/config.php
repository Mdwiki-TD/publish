<?php

declare(strict_types=1);

/**
 * OAuth configuration for MediaWiki API access.
 *
 * This module loads OAuth consumer credentials and encryption keys
 * from an INI configuration file. These credentials are used to
 * authenticate API requests to Wikipedia and Wikidata.
 *
 * SECURITY NOTE: Configuration file should be outside web root
 * with restricted permissions (0600).
 *
 * @package Publish
 * @author  MDWiki Team
 * @since   1.0.0
 *
 * @see https://www.mediawiki.org/wiki/OAuth/For_Developers
 */

include_once __DIR__ . '/../vendor_load.php';

use Defuse\Crypto\Key;

/**
 * Root path for configuration files.
 *
 * Uses HOME environment variable in production, falls back to
 * Windows development path.
 *
 * @var string
 */
$ROOT_PATH = getenv("HOME") ?: 'I:/mdwiki/mdwiki';

/**
 * Path to the OAuth configuration INI file.
 *
 * @var string
 */
$inifile = $ROOT_PATH . '/confs/OAuthConfig.ini';

/**
 * Parsed configuration from INI file.
 *
 * @var array<string, string>|false
 */
$ini = parse_ini_file($inifile);

if ($ini === false) {
    header("HTTP/1.1 500 Internal Server Error");
    echo "The ini file:({$inifile}) could not be read";
    exit(0);
}

// Validate required configuration keys
if (
    !isset($ini['agent']) ||
    !isset($ini['consumerKey']) ||
    !isset($ini['consumerSecret'])
) {
    header("HTTP/1.1 500 Internal Server Error");
    echo 'Required configuration directives not found in ini file';
    exit(0);
}

/**
 * User agent string for API requests.
 *
 * Identifies the application in Wikipedia API logs.
 *
 * @var string
 */
$gUserAgent = 'mdwiki MediaWiki OAuth Client/1.0';

/**
 * Base OAuth URL for Wikimedia Meta-Wiki.
 *
 * All OAuth authentication flows go through Meta-Wiki.
 *
 * @var string
 */
$oauthUrl = 'https://meta.wikimedia.org/w/index.php?title=Special:OAuth';

/**
 * MediaWiki API URL derived from OAuth URL.
 *
 * @var string
 */
$apiUrl = preg_replace('/index\.php.*/', 'api.php', $oauthUrl);

/**
 * OAuth consumer key (public identifier).
 *
 * Registered at https://meta.wikimedia.org/wiki/Special:OAuthConsumerRegistration
 *
 * @var string
 */
$consumerKey = $ini['consumerKey'] ?? '';

/**
 * OAuth consumer secret (kept confidential).
 *
 * SECURITY NOTE: This should never be exposed in client-side code or logs.
 *
 * @var string
 */
$consumerSecret = $ini['consumerSecret'] ?? '';

/**
 * OAuth consumer key for new authentication flow.
 *
 * @var string
 */
$consumerKey_new = $ini['consumerKey_new'] ?? '';

/**
 * OAuth consumer secret for new authentication flow.
 *
 * @var string
 */
$consumerSecrety_new = $ini['consumerSecrety_new'] ?? '';

/**
 * Current server domain name.
 *
 * @var string
 */
$domain = $_SERVER['SERVER_NAME'] ?? 'localhost';

/**
 * Encryption key for cookie data.
 *
 * Used with Defuse Crypto for secure cookie storage.
 *
 * @var Key
 */
$cookie_key = $ini['cookie_key'] ?? '';
$cookie_key = Key::loadFromAsciiSafeString($cookie_key);

/**
 * Encryption key for general data encryption.
 *
 * Used for encrypting OAuth tokens and sensitive data in database.
 *
 * @var Key
 */
$decrypt_key = $ini['decrypt_key'] ?? '';
$decrypt_key = Key::loadFromAsciiSafeString($decrypt_key);

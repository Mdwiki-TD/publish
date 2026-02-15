<?php
//---
include_once __DIR__ . '/../vendor_load.php';
//---
$gUserAgent = 'mdwiki MediaWiki OAuth Client/1.0';
$oauthUrl = 'https://meta.wikimedia.org/w/index.php?title=Special:OAuth';

// Make the api.php URL from the OAuth URL.
$apiUrl = preg_replace('/index\.php.*/', 'api.php', $oauthUrl);
$domain = $_SERVER['SERVER_NAME'] ?? 'localhost';

use Defuse\Crypto\Key;
//---
$ROOT_PATH = getenv("HOME") ?: 'I:/mdwiki/mdwiki';
//---
$inifile = $ROOT_PATH . '/confs/OAuthConfig.ini';
//---
$ini = parse_ini_file($inifile);
//---
if ($ini === false) {
    header("HTTP/1.1 500 Internal Server Error");
    echo "The ini file:($inifile) could not be read";
    exit(0);
}
if (
    !isset($ini['agent']) ||
    !isset($ini['consumerKey']) ||
    !isset($ini['consumerSecret'])
) {
    header("HTTP/1.1 500 Internal Server Error");
    echo 'Required configuration directives not found in ini file';
    exit(0);
}

$consumerKey    = $ini['consumerKey'] ?? '';
$consumerSecret = $ini['consumerSecret'] ?? '';
$cookie_key     = $ini['cookie_key'] ?? '';
$decrypt_key    = $ini['decrypt_key'] ?? '';

$cookie_key = Key::loadFromAsciiSafeString($cookie_key);
$decrypt_key = Key::loadFromAsciiSafeString($decrypt_key);

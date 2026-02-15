<?php
//---
include_once __DIR__ . '/../vendor_load.php';
//---
$oauthUrl = 'https://meta.wikimedia.org/w/index.php?title=Special:OAuth';

// Make the api.php URL from the OAuth URL.
$apiUrl = preg_replace('/index\.php.*/', 'api.php', $oauthUrl);
$domain = $_SERVER['SERVER_NAME'] ?? 'localhost';

use Defuse\Crypto\Key;

// ----------------
// ----------------
$CONSUMER_KEY        = getenv("CONSUMER_KEY") ?: '';
$CONSUMER_SECRET     = getenv("CONSUMER_SECRET") ?: '';
$_cookie_key_str     = getenv("COOKIE_KEY") ?: '';
$_decrypt_key_str    = getenv("DECRYPT_KEY") ?: '';
// ----------------
// ----------------

if (empty($CONSUMER_KEY) || empty($CONSUMER_SECRET)) {
    header("HTTP/1.1 500 Internal Server Error");
    echo 'Required configuration directives not found in ini file';
    exit(0);
}

$cookie_key  = Key::loadFromAsciiSafeString($_cookie_key_str);
$decrypt_key = Key::loadFromAsciiSafeString($_decrypt_key_str);
